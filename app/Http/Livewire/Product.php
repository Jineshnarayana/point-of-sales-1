<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Product as ProductModel;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class Product extends Component
{

    use WithFileUploads;

    public $name,$image,$description,$qty,$price;
    
    public function render()
    {
        $products = ProductModel::orderBy('created_at', 'DESC')->get();
        return view('livewire.product',[
            'products' => $products
        ]);
    }

    public function previewImage(){
        $this->validate([
            'image' => 'image|max:2048'
        ]);
    }

    public function store(){
        $this->validate([
            'name' => 'required',
            'image' => 'image|max:2048|required',
            'description' => 'required',
            'qty' => 'required',
            'price' => 'required', 
        ]);

        $imageName = md5($this->image.microtime().'.'.$this->image->extension());

        Storage::putFileAs(
            'public/images',
            $this->image,
            $imageName
        );

        ProductModel::create([
            'name'=> $this->name,
            'image' => $imageName,
            'description' => $this->description,
            'qty' => $this->qty,
            'price' => $this->price   
        ]);

        session()->flash('info', 'Product Created Sucessfully');

        $this->name = "";
        $this->image = "";
        $this->description = "";
        $this->qty = "";
        $this->price = "";
    }
}
