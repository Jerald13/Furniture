<?php
namespace App\Repositories;

use App\Repositories\Interfaces\StockRepositoryInterface;
use App\Models\Stock;

class StockRepository implements StockRepositoryInterface
{
    
    public function getAll()
    {
        return Stock::all();
    }

    public function getById($id)
    {
        return Stock::find($id);
    }

    public function create($data)
    {
        return Stock::create($data);
    }

    public function update($id, $data)
    {
        $prod = Stock::find($id);
        $prod->update($data);
        return $prod;
    }

    public function delete($id)
    {
        Stock::destroy($id);
    }
}

