<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProductRequest;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Category;
use App\Models\User;
use App\Models\Product_images;



use App\Models\Order;
use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\SizeRepository;
use App\Repositories\ColorRepository;
use App\Repositories\UserRepository;
use App\Repositories\StockRepository;


use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{

    protected $prodRespository;
    protected $sizeRepository;
    protected $colorRepository;
    protected $cateRepository;
    protected $stockRepository;




    public function __construct(ProductRepository $prodRespository, CategoryRepository $cateRepository, SizeRepository $sizeRepository, ColorRepository $colorRepository, StockRepository $stockRepository)
    {

        $this->prodRespository = $prodRespository;
        $this->cateRepository = $cateRepository;
        $this->sizeRepository = $sizeRepository;
        $this->colorRepository = $colorRepository;
        $this->stockRepository = $stockRepository;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createProduct(Request $request)
    {

        $data = $request;

        $sizes = $data->input('size');
        $colors = $data->input('color');
        $quantity = $data->input('quantity');

        //table product insert
        $product = $this->prodRespository->create([
            'category_id' => $data['category_id'],
            'name' => $data['prodName'],
            'description' => $data['prodDesc'],
            'price' => $data['prodPrice'],
        ]);


        $latestProdId = $this->prodRespository->getLatestId();
        $this->stockRepository->create([
            'color_id' => sizeof($sizes),
            'size_id' => $sizes[0],
            'product_id' => $latestProdId,
            'quantity' => $quantity[0],

        ]);

        $this->upload($data, $latestProdId);

        return view("testing");
    }

    public function upload(Request $request, $latestProdId)
    {
        $validatedData = $request->validate([
            "images.*" => "required|image|mimes:jpeg,png,jpg,gif|max:20000",
        ]);

        $images = $request->file('image');
        $imageData = [];

        foreach ($images as $image) {
            $imageData[] = [
                "name" => $image->getClientOriginalName(),
                "data" => file_get_contents($image),
                "mime" => $image->getClientMimeType(),
                "product_id" => $latestProdId,
            ];
        }
        Product_images::insert($imageData);
        try {
        } catch (\Illuminate\Database\QueryException $e) {
            // handle the exception, log the error, or return a custom error response
            return response()->json(
                [
                    "error" =>
                    "An error occurred while running a database query.",
                ],
                500
            );
        }

        return redirect()
            ->back()
            ->with("success", "Images uploaded successfully.");
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function displayCreateForm()
    {
        $categories = $this->cateRepository->allCategories();
        $sizes = $this->sizeRepository->getAll();
        $colors = $this->colorRepository->getAll();


        return view("editor.productCreate", compact('categories', 'sizes', 'colors'));
    }

    /**
     * Display a listing of the resource.
     *
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        // $products = Product::all();
        // return view ('index')->with(compact('products'));
        // $products = Product::all();
        // return view("index", compact("products"));
        $data = Product::all();
        return view("product", ["products" => $data]);
    }


    public function shop()
    {
        $products = $this->prodRespository->getAll();
        $categories =  $this->cateRepository->allCategories();
        return view('shop', compact('products', 'categories'));
    }

    function detail($id)
    {
        $data = Product::find($id);
        return view("detail", ["product" => $data]);
    }

    function search(Request $req)
    {
        $data = Product::where(
            "name",
            "like",
            "%" . $req->input("query") . "%"
        )->get();
        return view("search", ["products" => $data]);
    }

    function addToCart(Request $req)
    {
        if ($req->session()->has("user")) {
            $cart = new Cart();
            $cart->user_id = $req->session()->get("user")["id"];
            $cart->product_id = $req->product_id;
            $cart->save();
            return redirect("/");
        } else {
            return redirect("/login");
        }
    }

    static function cartItem()
    {
        $userId = Session::get("user")["id"];
        return Cart::where("user_id", $userId)->count();
    }

    static function cartList()
    {
        $userId = Session::get("user")["id"];

        $products = DB::table("cart")
            ->join("products", "cart.product_id", "=", "products.id")
            ->where("cart.user_id", $userId)
            ->select("products.*", "cart.id as cart_id")
            ->get();
        return view("cartList", ["products" => $products]);
    }

    function removeCart($id)
    {
        Cart::destroy($id);
        return redirect("cartlist");
    }

    function orderNow()
    {
        $userId = Session::get("user")["id"];
        $total = $products = DB::table("cart")
            ->join("products", "cart.product_id", "=", "products.id")
            ->where("cart.user_id", $userId)
            ->sum("products.price");
        return view("ordernow", ["total" => $total]);
    }

    function orderPlace(Request $req)
    {
        $userId = Session::get("user")["id"];
        $allCart = Cart::where("user_id", $userId)->get();
        foreach ($allCart as $cart) {
            $order = new Order();
            $order->product_id = $cart["product_id"];
            $order->user_id = $cart["user_id"];
            $order->status = "pending";
            $order->payment_method = $req->payment;
            $order->payment_status = "pending";
            $order->address = $req->address;
            $order->save();
            Cart::where("user_id", $userId)->delete();
        }
        $req->input();
        return redirect("/");
    }
    function myOrders()
    {
        $userId = Session::get("user")["id"];
        $user = User::find($userId);
        $orders = $user
            ->myOrder()
            ->with("product")
            ->get();
        return view("myorders", compact("orders"));
        // $orders = DB::table("orders")
        //     ->join("products", "orders.product_id", "=", "products.id")
        //     ->where("orders.user_id", $userId)
        //     ->get();
        // return view("myorders", ["orders" => $orders]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $product = new Product();
        $product->code = $request->get("code");
        $product->name = $request->get("name");
        $product->save();
        return redirect("products")->with(
            "success",
            "Information has been added"
        );
        // return redirect('products')->with('success', 'Information has been added');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return view("index");
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $product = Product::find($id);
        return view("edit", ["product" => $product]);
        // return view('products.edit', ['product' => $product]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        $product->code = $request->get("code");
        $product->name = $request->get("name");
        $product->save();
        return redirect("products");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        $product->delete();
        return redirect("products")->with(
            "success",
            "Information has been deleted"
        );
    }
}
