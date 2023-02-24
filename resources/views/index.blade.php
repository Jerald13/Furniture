<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ asset('public/css/app.css') }}">
    <title>Index Page</title>
</head>
<body>
    <div class="container">
        <br/>
        @if (\Session::has('success'))
        <div class="alert alert-success">
            <p>{{ \Session::get('success') }}</p>
        </div><br />
        @endif
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>
                        ID
                    </th>
                    <th>
                        Product Code
                    </th>
                    <th>
                        Product Name
                    </th>
                    <th colspan="2">
                        Action
                    </th>
                </tr>
     
                @foreach($products as $product)
                <tr>
                    <td>{{ $product['id'] }}</td>
                    <td>{{ $product['code'] }}</td>
                    <td>{{ $product['name'] }}</td>
                    <td>
                        <a href="{{ action('ProductController@edit',$product['id']) }}" class="btn btn-warning">Edit</a>
                    </td>
                    <td>
                        <form action="{{ action('ProductController@destroy', $product['id']) }}" method="post">
                            @csrf
                            <input name="_method" type="hidden" value="DELETE">
                            <button class="btn btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </thead>
        </table>

    </div>
    
</body>
</html>