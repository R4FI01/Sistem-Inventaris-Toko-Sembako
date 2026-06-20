<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class ProductController extends Controller
{
    function ProductPage(): View
    {
        return view('pages.dashboard.product-page');
    }

    function CreateProduct(Request $request)
    {
        $user_id = $request->header('id');

        $request->validate([
            'category_id' => 'required',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|integer|min:0',
            'img' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
        ], [
            'category_id.required' => 'Kategori produk wajib dipilih.',
            'name.required' => 'Nama produk wajib diisi.',
            'price.required' => 'Harga produk wajib diisi.',
            'price.numeric' => 'Harga produk harus berupa angka.',
            'unit.required' => 'Jumlah stok wajib diisi.',
            'unit.integer' => 'Jumlah stok harus berupa angka bulat.',
            'img.required' => 'Gambar produk wajib diunggah.',
            'img.image' => 'File harus berupa gambar.',
            'img.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp.',
            'img.max' => 'Ukuran gambar maksimal 2 MB.'
        ]);

        $img = $request->file('img');

        $t = time();
        $extension = $img->getClientOriginalExtension();
        $img_name = "{$user_id}-{$t}." . $extension;
        $img_url = "uploads/{$img_name}";

        $img->move(public_path('uploads'), $img_name);

        return Product::create([
            'name' => $request->input('name'),
            'price' => $request->input('price'),
            'unit' => $request->input('unit'),
            'img_url' => $img_url,
            'category_id' => $request->input('category_id'),
            'user_id' => $user_id
        ]);
    }

    function DeleteProduct(Request $request)
    {
        $user_id = $request->header('id');
        $product_id = $request->input('id');
        $filePath = $request->input('file_path');

        File::delete($filePath);

        return Product::where('id', $product_id)
            ->where('user_id', $user_id)
            ->delete();
    }

    function ProductByID(Request $request)
    {
        $user_id = $request->header('id');
        $product_id = $request->input('id');

        return Product::where('id', $product_id)
            ->where('user_id', $user_id)
            ->first();
    }

    function ProductList(Request $request)
    {
        $user_id = $request->header('id');

        return Product::where('user_id', $user_id)->get();
    }

    function UpdateProduct(Request $request)
    {
        $user_id = $request->header('id');
        $product_id = $request->input('id');

        $request->validate([
            'id' => 'required',
            'category_id' => 'required',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|integer|min:0',
            'img' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ], [
            'category_id.required' => 'Kategori produk wajib dipilih.',
            'name.required' => 'Nama produk wajib diisi.',
            'price.required' => 'Harga produk wajib diisi.',
            'price.numeric' => 'Harga produk harus berupa angka.',
            'unit.required' => 'Jumlah stok wajib diisi.',
            'unit.integer' => 'Jumlah stok harus berupa angka bulat.',
            'img.image' => 'File harus berupa gambar.',
            'img.mimes' => 'Format gambar harus jpg, jpeg, png, atau webp.',
            'img.max' => 'Ukuran gambar maksimal 2 MB.'
        ]);

        if ($request->hasFile('img')) {
            $img = $request->file('img');

            $t = time();
            $extension = $img->getClientOriginalExtension();
            $img_name = "{$user_id}-{$t}." . $extension;
            $img_url = "uploads/{$img_name}";

            $img->move(public_path('uploads'), $img_name);

            $filePath = $request->input('file_path');

            if ($filePath && File::exists(public_path($filePath))) {
                File::delete(public_path($filePath));
            }

            return Product::where('id', $product_id)
                ->where('user_id', $user_id)
                ->update([
                    'name' => $request->input('name'),
                    'price' => $request->input('price'),
                    'unit' => $request->input('unit'),
                    'img_url' => $img_url,
                    'category_id' => $request->input('category_id')
                ]);
        }

        return Product::where('id', $product_id)
            ->where('user_id', $user_id)
            ->update([
                'name' => $request->input('name'),
                'price' => $request->input('price'),
                'unit' => $request->input('unit'),
                'category_id' => $request->input('category_id')
            ]);
    }
}