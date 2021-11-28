<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();
Route::get('active/{token}', 'Auth\RegisterController@activation')->name('active_account');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::namespace('Admin')->prefix('admin')->name('admin.')->middleware('admin')
    ->group(function () {
        Route::get('dashboard', 'DashboardController@index')->name('dashboard');

        Route::get('users', 'UserController@index')->name('users');
        Route::post('user/new', 'UserController@new')->name('user_new');
        Route::post('user/delete', 'UserController@delete')->name('user_delete');
        Route::get('user/{id}/show', 'UserController@show')->name('user_show');
        Route::get('user/{id}/send', 'UserController@send')->name('user_send');

        Route::get('posts', 'PostController@index')->name('post.index');
        Route::get('post/new', 'PostController@new')->name('post.new');
        Route::post('post/save', 'PostController@save')->name('post.save');
        Route::post('post/delete', 'PostController@delete')->name('post.delete');
        Route::get('post/{id}/edit', 'PostController@edit')->name('post.edit');
        Route::post('post/{id}/update', 'PostController@update')->name('post.update');

        Route::get('advertises', 'AdvertiseController@index')->name('advertise.index');
        Route::get('advertise/new', 'AdvertiseController@new')->name('advertise.new');
        Route::post('advertise/save', 'AdvertiseController@save')->name('advertise.save');
        Route::post('advertise/delete', 'AdvertiseController@delete')->name('advertise.delete');
        Route::get('advertise/{id}/edit', 'AdvertiseController@edit')->name('advertise.edit');
        Route::post('advertise/{id}/update', 'AdvertiseController@update')->name('advertise.update');

        Route::get('products', 'ProductController@index')->name('product.index');
        Route::get('product/new', 'ProductController@new')->name('product.new');
        Route::post('product/save', 'ProductController@save')->name('product.save');
        Route::post('product/delete', 'ProductController@delete')->name('product.delete');
        Route::get('product/{id}/edit', 'ProductController@edit')->name('product.edit');
        Route::post('product/{id}/update', 'ProductController@update')->name('product.update');

        Route::get('product-categories', 'ProductCategoryController@index')->name('product-category.index');
        Route::get('product-category/new', 'ProductCategoryController@new')->name('product-category.new');
        Route::post('product-category/save', 'ProductCategoryController@save')->name('product-category.save');
        Route::post('product-category/delete', 'ProductCategoryController@delete')->name('product-category.delete');
        Route::get('product-category/{id}/edit', 'ProductCategoryController@edit')->name('product-category.edit');
        Route::post('product-category/{id}/update', 'ProductCategoryController@update')->name('product-category.update');

        Route::post('promotion/delete', 'ProductController@delete_promotion')->name('product.delete_promotion');
        Route::post('product_detail/delete', 'ProductController@delete_product_detail')->name('product.delete_product_detail');
        Route::post('product/image/delete', 'ProductController@delete_image')->name('product.delete_image');

        Route::get('orders', 'OrderController@index')->name('order.index');
        Route::get('order/{id}/show', 'OrderController@show')->name('order.show');

        Route::get('statistic', 'StatisticController@index')->name('statistic');
        Route::post('statistic/change', 'StatisticController@edit')->name('statistic.edit');
    });

Route::namespace('Pages')->group(function () {
    Route::get('/', 'HomePage')->name('home_page');
    Route::get('gioi-thieu', 'AboutPage')->name('about_page');
    Route::get('lien-he', 'ContactPage')->name('contact_page');
    Route::get('tim-kiem', 'SearchController')->name('search');
    Route::get('tin-tuc', 'PostController@index')->name('posts_page');
    Route::get('tin-tuc/{slug}', 'PostController@show')->name('post_page');
    Route::get('orders', 'OrderController@index')->name('orders_page');
    Route::get('order/{id}', 'OrderController@show')->name('order_page');
    Route::get('user/profile', 'UserController@show')->name('show_user');
    Route::get('user/edit', 'UserController@edit')->name('edit_user');
    Route::post('user/save', 'UserController@save')->name('save_user');
    //page products
    Route::get('san-pham', 'ProductsController@index')->name('products_page');
    Route::get('danh-muc/{id}', 'ProductsController@getProducer')->name('producer_page');
    Route::get('san-pham/{slug}', 'ProductsController@getProduct')->name('product_page');
    Route::post('vote', 'ProductsController@addVote')->name('add_vote');
    Route::post('cart/add', 'CartController@addCart')->name('add_cart');
    Route::post('cart/remove', 'CartController@removeCart')->name('remove_cart');
    Route::post('minicart/update', 'CartController@updateMiniCart')->name('update_minicart');
    Route::post('cart/update', 'CartController@updateCart')->name('update_cart');
    Route::get('gio-hang', 'CartController@showCart')->name('show_cart');
    Route::post('thanh-toan', 'CartController@showCheckout')->name('show_checkout');
    Route::post('payment', 'CartController@payment')->name('payment');
    Route::get('xac-thuc-thanh-toan', 'CartController@responsePayment')->name('payment_response');
});
