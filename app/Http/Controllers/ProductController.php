<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Cart;
use Illuminate\Support\Facades\Session;

class ProductController extends Controller
{
    //

    public function addproduct(){
        $categories = Category::all()->pluck('category_name', 'category_name');
        return view('admin.addproduct')->with('categories', $categories);
    }

    public function products(){
        $products = Product::all();
        return view('admin.products')->with('products', $products);
    }

    public function saveproduct(Request $request){
        $this->validate($request, [
            'product_name'      => 'required',
            'product_price'     => 'required|numeric',
            'product_category'  => 'required',
            'product_image'     => 'image|nullable|max:1999'
        ]);
        if($request->hasFile('product_image')){
            // print('Image selectionne');
            $fileNameWithExt = $request->file('product_image')->getClientOriginalName();
            $fileName        = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            $extension       = $request->file('product_image')->getClientOriginalExtension();
            $fileNameToStore = $fileName.'_'.time().'.'.$extension;
            $path            = $request->file('product_image')->storeAs('public/product_images', $fileNameToStore);
        }else{
            $fileNameToStore = 'noimage.jpeg';
        }
        $product = new Product();
        $product->product_name      = $request->input('product_name');
        $product->product_price     = $request->input('product_price');
        $product->product_category  = $request->input('product_category');
        $product->product_image     = $fileNameToStore;
        $product->status            = 1;
        $product->save();
        return back()->with('status', "Le produit a été enregistré avec succès!");
    }

    public function edit_product($id){
        $product    = Product::find($id);
        $categories = Category::all()->pluck('category_name', 'category_name');
        // return view('admin.editproduct')->with([ 'product' => $product, 'categories' => $categories]);
        return view('admin.editproduct')->with('categories', $categories)->with('product', $product);
    }

    public function updateproduct(Request $request){
        $this->validate($request, [
            'product_name'      => 'required',
            'product_price'     => 'required|numeric',
            'product_category'  => 'required',
            'product_image'     => 'image|nullable|max:1999'
        ]);
        $product = Product::find($request->input('id'));
        $product->product_name      = $request->input('product_name');
        $product->product_price     = $request->product_price;
        $product->product_category  = $request->input('product_category');
        if($request->hasFile('product_image')){
            // print('Image selectionne');
            $fileNameWithExt = $request->file('product_image')->getClientOriginalName();
            $fileName        = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            $extension       = $request->file('product_image')->getClientOriginalExtension();
            $fileNameToStore = $fileName.'_'.time().'.'.$extension;
            $path            = $request->file('product_image')->storeAs('public/product_images', $fileNameToStore);
            if($product->product_image != 'noimage.jpeg'){
                Storage::delete('public/product_images/'.$product->product_image);
            }
            $product->product_image     = $fileNameToStore;
        }
        $product->update();
        return redirect('/products')->with('status', "Le produit a été modifié avec succès!");
    }

    public function delete_product($id){
        $product = Product::find($id);
        if($product->product_image != 'noimage.jpeg'){
            Storage::delete('public/product_images/'.$product->product_image);
        }
        $product->delete();
        return back()->with('status', "Le produit a été supprimé avec succès!");
    }

    public function activer_product($id){
        $product = Product::find($id);
        $product->status = 1;
        $product->update();
        return back();
    }

    public function desactiver_product($id){
        $product = Product::find($id);
        $product->status = 0;
        $product->update();
        return back();
    }

    public function status_product($id){
        $product = Product::find($id);
        $product->status = !$product->status;
        $product->update();
        return back();
    }


    public function select_par_cat($category_name){
        $products = Product::all()->where('product_category', $category_name)->where('status', 1);
        $categories = Category::all();
        return view('client.shop')->with('products', $products)->with('categories', $categories);
    }

    // public function ajouteraupanier($id){
    //     $product = Product::find($id);
    //     $oldCart = Session::has('cart')? Session::get('cart'):null;
    //     $cart = new Cart($oldCart);
    //     $cart->add($product, $id);
    //     Session::put('cart', $cart);

    //     // dd(Session::get('cart'));
    //     // return redirect::to('/shop');
    //     return back();
    // }

}
