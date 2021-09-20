<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Product as ProductModel;
use Carbon\Carbon;
use Livewire\WithPagination;
use DB;

class Cart extends Component
{

    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $tax = "0%";

    public $search;

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        $products = ProductModel::where('name','like','%'.$this->search.'%')->orderBy('created_at', 'DESC')->paginate(4);

        //condition utk hitung pajak
        $condition = new \Darryldecode\Cart\CartCondition([
            'name' => 'pajak',
            'type' => 'tax',
            'target' => 'total',
            'value' => $this->tax,
            'order' => 1
        ]);

        //membuat id unik utk setiap chart dari user yang login
        \Cart::session(Auth()->id())->condition($condition);
        //sort chart by date
        $items = \Cart::session(Auth()->id())->getContent()->sortBy(function($cart){
            return $cart->attributes->get('added_at');
        });

        //cek jika chart empty
        if(\Cart::isEmpty()){
            $cartData = [];//jika kosong
        }else{
            foreach($items as $item){//jika chart ada diisi ke array
                $cart[] = [
                    'rowId' => $item->id,
                    'name' => $item->name,
                    'qty' => $item->quantity,
                    'pricesingle' => $item->price,
                    'price' => $item->getPriceSum(),
                ];
            }
            $cartData = collect($cart);
        }
        //dapatkan sub total dari chart
        $sub_total = \Cart::session(Auth()->id())->getSubTotal();
        //total dengan pajak
        $total =  \Cart::session(Auth()->id())->getTotal();

        //handle pajak
        $newCondition = \Cart::session(Auth()->id())->getCondition('pajak');
        //pajak yang harus dibayar
        $pajak = $newCondition->getCalculatedValue($sub_total);

        //Menampung data subtotal, total dan pajak
        $summary = [
            'sub_total' => $sub_total,
            'pajak' => $pajak,
            'total' => $total
        ];

        return view('livewire.cart',[
            'products' => $products,
            'carts' => $cartData,
            'summary' => $summary,
        ]);
    }

    public function addItem($id){
        //row id harus unik
        $rowId = "Cart".$id;
        $cart = \Cart::session(Auth()->id())->getContent();//panggil cart dari session id
        $cekItemId = $cart->whereIn('id', $rowId);

        //jika barang yang dipilih sudah ada maka update quantity
        if($cekItemId->isNotEmpty()){
            \Cart::session(Auth()->id())->update($rowId, [
                'quantity' => [
                    'relative' => true,
                    'value' => 1
                ]
            ]);
            
        }else{
            $product = ProductModel :: findOrFail($id);
            \Cart::session(Auth()->id())->add([
                'id' => "Cart".$product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
                'attributes' => [
                    'added_at' => Carbon::now()
                ],    
            ]);
        }

    }
    public function enableTax(){
        $this->tax = "+10%";
    }
    public function disableTax(){
        $this->tax = "0%";
    }

    public function increaseItem($rowId){
       $idProduct = substr($rowId, 4,5);
       $product = ProductModel::find($idProduct);
       $cart = \Cart::session(Auth()->id())->getContent();

       $checkItem = $cart->wherein('id', $rowId);
       if($product->qty == $checkItem[$rowId]->quantity){
           session()->flash('error', 'jumlah item kurang');
       }else{
            \Cart::session(Auth()->id())->update($rowId,[
                'quantity' => [
                    'relative' => true,
                    'value' => 1
                ]
            ]);
       }

    }
    public function decreaseItem($rowId){
       
        $idProduct = substr($rowId, 4,5);
        $product = ProductModel::find($idProduct);
        $cart = \Cart::session(Auth()->id())->getContent();
 
        $checkItem = $cart->wherein('id', $rowId);
        if($checkItem[$rowId]->quantity == 1){
           $this->removeItem($rowId);
        }else{
            \Cart::session(Auth()->id())->update($rowId,[
                'quantity' => [
                    'relative' => true,
                    'value' => -1
                ]
            ]);
        }

    }
    public function removeItem($rowId){
       
        \Cart::session(Auth()->id())->remove($rowId);
    }

    public function handleSubmit(){
        $cartTotal = \Cart::session(Auth()->id())->getTotal();

        DB::beginTransaction();
        try {
            $allCart = \Cart::session(Auth()->id())->getContent();
            $filterCart = $allCart->map(function($item){
                return[
                    'id' => substr($item->id, 4,5),
                    'quantity' => $item->quantity
                ];
            });

            foreach ($filterCart as $cart) {
                $product = ProductModel::find($cart['id']);

                if($product->qty ===0){
                    return session()->flash('error', 'Jumlah item kurang');
                }

                $product->decrement('qty', $cart['quantity']);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();

            return session()->flash('error', $th);
        }
    }
}
