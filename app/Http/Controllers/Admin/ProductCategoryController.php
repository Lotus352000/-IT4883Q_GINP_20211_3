<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use App\Models\Product;
use App\Models\Producer;
use App\Models\Promotion;
use App\Models\ProductDetail;
use App\Models\ProductImage;
use App\Models\OrderDetail;

class ProductCategoryController extends Controller {
    public function index()
    {
        $categories = Producer::query()->withCount('products')->latest()->get();
        return view('admin.product-category.index')->with('categories', $categories);
    }

    public function delete(Request $request)
    {
        $category = Producer::where('id', $request->product_category_id)->whereDoesntHave('products')->first();

        if (!$category) {
            $data['type']    = 'error';
            $data['title']   = 'Thất Bại';
            $data['content'] = 'Danh mục không tồn tại hoặc đang có sản phẩm!';
        } else {

            $can_delete = 1;

            if ($can_delete) {
                $category->delete();
            } else {

            }

            $data['type']    = 'success';
            $data['title']   = 'Thành Công';
            $data['content'] = 'Xóa danh mục thành công!';
        }

        return response()->json($data, 200);
    }

    public function new(Request $request)
    {
        return view('admin.product-category.new');
    }

    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            // 'content' => 'required',
            // 'image'   => 'required',
            'slug'  => 'unique:producers',
        ], [
            'title.required' => 'Tên danh mục không được để trống!',
            // 'content.required' => 'Nội dung bài viết không được để trống!',
            // 'image.required'   => 'Hình ảnh hiển thị bài viết phải được tải lên!',
            'slug.unique'    => 'Danh mục đã tồn tại!',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        //Xử lý Ảnh trong nội dung
        $content = $request->content;

        $dom = new \DomDocument();

        if ($content) {
            // conver utf-8 to html entities
            $content = mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8");

            $dom->loadHtml($content, LIBXML_HTML_NODEFDTD);

            $images = $dom->getElementsByTagName('img');

            foreach ($images as $k => $img) {

                $data = $img->getAttribute('src');

                if (Str::containsAll($data, ['data:image', 'base64'])) {

                    list(, $type) = explode('data:image/', $data);
                    list($type,) = explode(';base64,', $type);

                    list(, $data) = explode(';base64,', $data);

                    $data = base64_decode($data);

                    $image_name = time() . $k . '.' . $type;

                    Storage::disk('public')->put('images/posts/' . $image_name, $data);

                    $img->removeAttribute('src');
                    $img->setAttribute('src', '/storage/images/posts/' . $image_name);
                }
            }

            $content = $dom->saveHTML();

            //conver html-entities to utf-8
            $content = mb_convert_encoding($content, "UTF-8", 'HTML-ENTITIES');

            //get content
            list(, $content) = explode('<html><body>', $content);
            list($content,) = explode('</body></html>', $content);
        }

        $producer          = new Producer();
        $producer->name    = $request->title;
        $producer->slug    = !empty($request->slug) ? $request->slug : str_slug($request->title);
        $producer->content = $content;
        $producer->status  = 1;

        // if ($request->hasFile('image')) {
        //     $image      = $request->file('image');
        //     $image_name = time() . '_' . $image->getClientOriginalName();
        //     $image->storeAs('images/posts', $image_name, 'public');
        //     producer->image = $image_name;
        // }

        $producer->save();

        return redirect()->route('admin.product-category.index')->with([
            'alert' => [
                'type'    => 'success',
                'title'   => 'Thành Công',
                'content' => 'Tạo danh mục sản phẩm thành công.'
            ]
        ]);
    }

    public function edit($id)
    {
        $category = Producer::where('id', $id)->first();
        if (!$category)
            abort(404);
        return view('admin.product-category.edit')->with('category', $category);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            // 'content' => 'required',
            'slug'  => 'unique:producers,slug,' . $id,
        ], [
            'title.required' => 'Tên danh mục không được để trống!',
            // 'content.required' => 'Nội dung không được để trống!',
            'slug.unique'    => 'Danh mục đã tồn tại!',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        //Xử lý Ảnh trong nội dung
        $content = $request->content;

        if ($content) {

            $dom = new \DomDocument();

            // conver utf-8 to html entities
            $content = mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8");

            $dom->loadHtml($content, LIBXML_HTML_NODEFDTD);

            $images = $dom->getElementsByTagName('img');

            foreach ($images as $k => $img) {

                $data = $img->getAttribute('src');

                if (Str::containsAll($data, ['data:image', 'base64'])) {

                    list(, $type) = explode('data:image/', $data);
                    list($type,) = explode(';base64,', $type);

                    list(, $data) = explode(';base64,', $data);

                    $data = base64_decode($data);

                    $image_name = time() . $k . '.' . $type;

                    Storage::disk('public')->put('images/posts/' . $image_name, $data);

                    $img->removeAttribute('src');
                    $img->setAttribute('src', '/storage/images/posts/' . $image_name);
                }
            }

            $content = $dom->saveHTML();

            //conver html-entities to utf-8
            $content = mb_convert_encoding($content, "UTF-8", 'HTML-ENTITIES');

            //get content
            list(, $content) = explode('<html><body>', $content);
            list($content,) = explode('</body></html>', $content);

        }

        $producer          = Producer::where('id', $id)->first();
        $producer->name    = $request->title;
        $producer->slug    = !empty($request->slug) ? $request->slug : str_slug($request->title);
        $producer->content = $content;

        // if ($request->hasFile('image')) {
        //     $image      = $request->file('image');
        //     $image_name = time() . '_' . $image->getClientOriginalName();
        //     $image->storeAs('images/posts', $image_name, 'public');
        //     Storage::disk('public')->delete('images/posts/' . $producer->image);
        //     $post->image = $image_name;
        // }

        $producer->save();

        return redirect()->route('admin.product-category.index')->with([
            'alert' => [
                'type'    => 'success',
                'title'   => 'Thành Công',
                'content' => 'Chỉnh sửa danh mục thành công.'
            ]
        ]);
    }
}
