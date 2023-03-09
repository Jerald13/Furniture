<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Notifications\MyNotification;
use App\Notifications\UserFollowNotification;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Response;

class UserController extends Controller
{
    // private $userRepository;

    // public function __construct(UserRepositoryInterface $userRepository)
    // {
    //     $this->userRepository = $userRepository;
    // }

    //Havent use Repository method

    function index(Request $request)
    {
        $user = User::where("email", $request->email)->first();
        // print_r($data);
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response(
                [
                    "message" => [
                        "These credentials do not match our records.",
                    ],
                ],
                404
            );
        }

        $token = $user->createToken("my-app-token")->plainTextToken;

        $response = [
            "user" => $user,
            "token" => $token,
        ];

        return response($response, 201);
    }

    function delete($id)
    {
        $user = User::find($id);
        $result = $user->delete();
        if ($result) {
            return ["result" => "record has been deleted"];
        }
    }

    function search($name)
    {
        return User::where("username", "like", "%" . $name . "%")->get();
    }

    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            "username" => ["required", "string", "max:255"],
            "email" => [
                "required",
                "string",
                "email",
                "max:255",
                Rule::unique("users")->ignore($user->id),
            ],
            "phone_number" => ["required", "string", "max:20"],
        ]);

        $user->update($validatedData);
        session()->flash("success", $user->username . " account updated.");
        return redirect("/profile");
        // $validator = Validator::make($request->all(), [
        //     "username" => "required|unique:posts|max:255",
        //     "phone_number" => "required",
        // ]);

        // if ($validator->fails()) {
        //     return redirect("profile")
        //         ->withErrors($validator)
        //         ->withInput();
        // }

        // $user->update($validatedData);
        // Session::put("user", $user->toArray());
        // session()->put("success", $user->username . " account updated.");
        // return redirect("profile");

        // session()->flash("success", $user->username . " account updated.");

        // return $validator->errors();
    }

    // public function update(Request $request, User $user)
    // {
    //     $validatedData = $request->validate([
    //         "username" => ["required", "string", "max:255"],
    //         "email" => [
    //             "required",
    //             "string",
    //             "email",
    //             "max:255",
    //             Rule::unique("users")->ignore($user->id),
    //         ],
    //         "phone_number" => ["required", "string", "max:20"],
    //     ]);

    //     $user->update($validatedData);

    //     session()->flash("success", $user->username . " account updated.");
    //     return redirect()->back();
    // }

    // function update(Request $req)
    // {
    //     $user = User::find($req->id);
    //     $user->username = $req->username;
    //     $user->email = $req->email;
    //     $user->password = Hash::make($req->password);
    //     $user->save();
    //     return ["Result" => "Data has been modified"];
    // }

    // function add(Request $req)
    // {
    //     $user = new User();
    //     $user->name = "User";
    //     $user->username = $req->username;
    //     $user->email = $req->email;

    //     $user->phone_number = $req->phone_code . $req->phone_number;
    //     $user->password = Hash::make($req->password);
    //     $user->save();

    //     return ["Result" => "Data has been saved"];
    // }

    function list($id = null)
    {
        return $id ? User::find($id) : User::all();
    }

    function login(Request $req)
    {
        $user = User::where(["email" => $req->email])->first();
        if (!$user || !Hash::check($req->password, $user->password)) {
            return "Username or password is not matched";
        } else {
            $req->session()->put("user", $user);
            return redirect("/");
        }
        $req->session()->put("user", $user);
    }

    function register(Request $req)
    {
        $req->validate([
            "username" => "required",

            "phone_code" => "required",
            "phone_number" => "required",
            "password" => "required|min:6",
            // "username" => ["required", "string", "max:255"],
            // "email" => [
            //     "required",
            //     "string",
            //     "email",
            //     "max:255",
            //     Rule::unique("users"),
            // ],
            // "phone_number" => ["required", "string", "max:20"],
            // 'password' => 'required|min:6'
        ]);

        $user = new User();
        $user->name = "User";
        $user->username = $req->username;
        $user->email = $req->email;
        $user->phone_number = $req->phone_code . $req->phone_number;

        $user->password = Hash::make($req->password);
        $user->save();

        return redirect("/login");
    }

    // function register(Request $req)
    // {
    //     // return $req->input();
    //     $user = new User();
    //     $user->name = "User";
    //     $user->username = $req->username;
    //     $user->email = $req->email;
    //     $user->phone_number = $req->phone_code . $req->phone_number;

    //     $user->password = Hash::make($req->password);
    //     $user->save();

    //     //This line of Code is Send SMS Notification from Vonage to User Phone number exactly
    //     // $user->notify(new MyNotification());

    //     // User::route("vonage", "+60182055007")->notify(new MyNotification());
    //     // $user->notify(new MyNotification(), ["vonage" => "+60182055007"]);
    //     // $user->notify((new MyNotification())->toVonage($user));

    //     return redirect("/login");
    // }

    function testData(Request $req)
    {
        $rules = [
            "id" => "required|min:2|max:4",
        ];
        $validator = Validator::make($req->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else {
            $user = new User();
            $user->name = $req->name;
            $user->email = $req->email;
            $user->password = Hash::make($req->password);
            $result = $user->save();
            if ($result) {
                return ["Result" => "Data has been modified"];
            } else {
                return ["Result" => "failed"];
            }
        }
    }

    public function profile()
    {
        $user = auth()->user();
        return view("profile", compact("user"));
    }

    public function displayInXSL()
    {
        $users = User::latest()->paginate(10);

        $xml = new \SimpleXMLElement("<users/>");

        foreach ($users as $user) {
            $userXml = $xml->addChild("user");
            $userXml->addChild("id", $user->id);
            $userXml->addChild("name", $user->name);
            $userXml->addChild("username", $user->username);
            $userXml->addChild("email", $user->email);
            $userXml->addChild("phone_number", $user->phone_number);
        }

        $xmlString = $xml->asXML();

        // Load the XSL stylesheet
        $xsl = new \DOMDocument();
        $xsl->load(base_path("resources/views/editor/User/index.xsl"));

        // Load the XML data
        $xmlData = new \DOMDocument();
        $xmlData->loadXML($xmlString);

        // Apply the XSL transformation
        $xsltProcessor = new \XSLTProcessor();
        $xsltProcessor->importStylesheet($xsl);
        $htmlString = $xsltProcessor->transformToXML($xmlData);

        // Create and return the response
        $response = new Response($htmlString);
        $response->header("Content-Type", "text/html");

        return $response;
    }

    public function displayInXML()
    {
        $users = User::latest()->paginate(10);

        $xml = new \SimpleXMLElement("<users/>");

        foreach ($users as $user) {
            $userXml = $xml->addChild("user");
            $userXml->addChild("id", $user->id);
            $userXml->addChild("name", $user->name);
            $userXml->addChild("username", $user->username);
            $userXml->addChild("email", $user->email);
            $userXml->addChild("phone_number", $user->phone_number);
        }

        $xmlString = $xml->asXML();

        $response = new Response($xmlString);
        $response->header("Content-Type", "application/xml");

        return $response;
    }
}
