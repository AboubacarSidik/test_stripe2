<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Slider;
use App\Models\Product;
use App\Models\Category;
use App\Models\Client;
use App\Models\Order;
use App\Cart;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use App\Mail\SendMail;
use Stripe\Charge;
use Stripe\Stripe;

class ClientController extends Controller
{
    //
    public function home(){
        $sliders    = Slider::all()->where('status', 1);
        $products   = Product::all()->where('status', 1);

        return view('client.home')->with(['sliders' => $sliders, 'products' => $products]);
    }

    public function shop(){
        $products   = Product::all()->where('status', 1);
        $categories = Category::all();
        return view('client.shop')->with('products', $products)->with('categories', $categories);
    }

    public function ajouteraupanier($id){
        $product = Product::find($id);
        $oldCart = Session::has('cart')? Session::get('cart'):null;
        $cart = new Cart($oldCart);
        $cart->add($product, $id);
        Session::put('cart', $cart);

        // dd(Session::get('cart'));
        // return redirect::to('/shop');
        return back();
    }

    public function modifier_quant(Request $request, $id){
        $oldCart = Session::has('cart')? Session::get('cart'):null;
        $cart = new Cart($oldCart);
        $cart->updateQty($id, $request->quantity);
        Session::put('cart', $cart);
        //dd(Session::get('cart'));
        return back();
    }

    public function suppdupanier($id){
        $oldCart = Session::has('cart')? Session::get('cart'):null;
        $cart = new Cart($oldCart);
        $cart->removeItem($id);
        if(count($cart->items) > 0){
            Session::put('cart', $cart);
        }else{
            Session::forget('cart');
        }
        //dd(Session::get('cart'));
        return back();
    }

    public function panier(){
        // if(!Session::has('cart')){
        //     return redirect('shop');
        // }
        $oldCart = Session::has('cart')? Session::get('cart'):null;
        $cart = new Cart($oldCart);
        return view('client.panier', ['products' => $cart->items]);
    }

    public function paiement(){
        if(!Session::has('client')){
            return redirect('login');
        }
        if(!Session::has('cart')){
            return redirect('panier');
        }

        return view('client.paiement');
    }

    public function payer(Request $request){

        // print_r($request['name']); exit;
        $oldCart    = Session::has('cart')? Session::get('cart'):null;
        $cart       = new Cart($oldCart);

        Stripe::setApiKey('sk_test_51MUsWcK3Nt9vRhqIkkNAb6KAdDlfzD9JSxLczeLI9s0lhNrShNxSBnPvcOTmCJrRco06AmQRxotwvKpy7kRLACze00bM8iDvHC');

        try{
            // https://stripe.com/docs/terminal/references/testing
            $charge = Charge::create(array(
                "amount"    => $cart->totalPrice * 100,
                "currency"  => "usd",
                "source"    => $request->input('stripeToken'), // obtainded with Stripe.js
                "description" => "Test Charge"
            ));
        } catch(\Exception $e){
            Session::put('error', $e->getMessage());
            return redirect('/paiement');
        }


        $order      = new Order();
        $payer_id   = time();

        $order->names   = $request->input('name');
        $order->adresse = $request->input('address');
        $order->panier  = serialize($cart);
        $order->payer_id= $payer_id;

        $order->save();
        Session::forget('cart');

        $orders = Order::where('payer_id', $payer_id)->get();
        $orders->transform(function($order, $key){
            $order->panier = unserialize($order->panier);
            return $order;
        });

        $email = Session::get('client');
        Mail::to($email)->send(new SendMail($orders));


        return redirect('/panier')->with('status', 'Votre commande a été effectuée avec succès !!');

    }

    public function login(){
        return view('client.login');
    }

    public function logout(){
        Session::forget('client');
        return back();
    }

    public function signup(){
        return view('client.signup');
    }

    public function creer_compte(Request $request){
        $this->validate($request,[
            'email'     => 'required|email|unique:clients',
            'password'  => 'required|min:4'
            ]
        );
        $client = new Client();
        $client->email      = $request->email;
        $client->password   = bcrypt($request->password);
        $client->save();
        return back()->with('status', 'Votre compte a été créé avec succès');
    }

    public function acceder_compte(Request $request){
        $this->validate($request,[
            'email'     => 'required|email',
            'password'  => 'required|min:4'
            ]
        );
        $client = Client::where('email', $request->input('email'))->first();
        if($client){
            if(Hash::check($request->input('password'), $client->password)){
                Session::put('client', $client);
                return redirect('/shop');
            }else{
                return back()->with('status', 'Mauvais mot de passe ou email');
            }
        }else{
            return back()->with('status', 'Pas de compte avec cet email, veuillez créer un compte');
        }
    }

    public function orders(){
        $orders = Order::all();
        $orders->transform(function($order, $key){
            $order->panier = unserialize($order->panier);
            return $order;
        });
        return view('admin.orders')->with('orders', $orders);
    }


}
