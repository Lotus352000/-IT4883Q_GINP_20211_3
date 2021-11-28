<?php

namespace App\Http\Controllers\Pages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use App\Models\ProductDetail;
use App\Models\Cart;
use App\Models\Advertise;
use App\Models\PaymentMethod;
use App\Models\Order;
use App\Models\OrderDetail;

class CartController extends Controller {
    public function addCart(Request $request)
    {

        $product = ProductDetail::where('id', $request->id)
            ->with([
                'product' => function ($query) {
                    $query->select('id', 'name', 'image', 'sku_code', 'RAM', 'ROM');
                }
            ])->select('id', 'product_id', 'color', 'quantity', 'sale_price', 'promotion_price', 'promotion_start_date', 'promotion_end_date')->first();

        if (!$product) {
            $data['msg'] = 'Product Not Found!';
            return response()->json($data, 404);
        }

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);
        if (!$cart->add($product, $product->id, $request->qty)) {
            $data['msg'] = 'Số lượng sản phẩm trong giỏ vượt quá số lượng sản phẩm trong kho!';
            return response()->json($data, 412); //respone error
        }
        Session::put('cart', $cart);

        $data['msg']      = "Thêm giỏ hàng thành công";
        $data['url']      = route('home_page');
        $data['response'] = Session::get('cart');

        return response()->json($data, 200);//response success
    }

    public function removeCart(Request $request)
    {

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);

        if (!$cart->remove($request->id)) {
            $data['msg'] = 'Sản Phẩm không tồn tại!';
            return response()->json($data, 404);
        } else {
            Session::put('cart', $cart);

            $data['msg']      = "Xóa sản phẩm thành công";
            $data['url']      = route('home_page');
            $data['response'] = Session::get('cart');

            return response()->json($data, 200);
        }
    }

    public function updateCart(Request $request)
    {
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);
        if (!$cart->updateItem($request->id, $request->qty)) {
            $data['msg'] = 'Số lượng sản phẩm trong giỏ vượt quá số lượng sản phẩm trong kho!';
            return response()->json($data, 412);
        }
        Session::put('cart', $cart);

        $response         = array(
            'id'         => $request->id,
            'qty'        => $cart->items[$request->id]['qty'],
            'price'      => $cart->items[$request->id]['price'],
            'salePrice'  => $cart->items[$request->id]['item']->sale_price,
            'totalPrice' => $cart->totalPrice,
            'totalQty'   => $cart->totalQty,
            'maxQty'     => $cart->items[$request->id]['item']->quantity
        );
        $data['response'] = $response;
        return response()->json($data, 200);
    }

    public function updateMiniCart(Request $request)
    {
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);
        if (!$cart->updateItem($request->id, $request->qty)) {
            $data['msg'] = 'Số lượng sản phẩm trong giỏ vượt quá số lượng sản phẩm trong kho!';
            return response()->json($data, 412);
        }
        Session::put('cart', $cart);

        $response         = array(
            'id'         => $request->id,
            'qty'        => $cart->items[$request->id]['qty'],
            'price'      => $cart->items[$request->id]['price'],
            'totalPrice' => $cart->totalPrice,
            'totalQty'   => $cart->totalQty,
            'maxQty'     => $cart->items[$request->id]['item']->quantity
        );
        $data['response'] = $response;
        return response()->json($data, 200);
    }

    public function showCart()
    {

        $advertises = Advertise::where([
            ['start_date', '<=', date('Y-m-d')],
            ['end_date', '>=', date('Y-m-d')],
            ['at_home_page', '=', false]
        ])->latest()->limit(5)->get(['product_id', 'title', 'image']);

        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart    = new Cart($oldCart);

        return view('pages.cart')->with(['cart' => $cart, 'advertises' => $advertises]);
    }

    public function showCheckout(Request $request)
    {
        if ($request->has('type') && $request->type == 'buy_now') {
            $payment_methods = PaymentMethod::select('id', 'name', 'describe')->get();
            $product         = ProductDetail::where('id', $request->id)
                ->with([
                    'product' => function ($query) {
                        $query->select('id', 'name', 'image', 'sku_code', 'RAM', 'ROM');
                    }
                ])->select('id', 'product_id', 'color', 'quantity', 'sale_price', 'promotion_price', 'promotion_start_date', 'promotion_end_date')->first();
            $cart            = new Cart(null);
            if (!$cart->add($product, $product->id, $request->qty)) {
                return back()->with([
                    'alert' => [
                        'type'    => 'warning',
                        'title'   => 'Thông Báo',
                        'content' => 'Số lượng sản phẩm trong giỏ vượt quá số lượng sản phẩm trong kho!'
                    ]
                ]);
            }
            return view('pages.checkout')->with(['cart' => $cart, 'payment_methods' => $payment_methods, 'buy_method' => $request->type]);
        } elseif ($request->has('type') && $request->type == 'buy_cart') {
            $payment_methods = PaymentMethod::select('id', 'name', 'describe')->get();
            $oldCart         = Session::has('cart') ? Session::get('cart') : null;
            $cart            = new Cart($oldCart);
            $cart->update();
            Session::put('cart', $cart);
            return view('pages.checkout')->with(['cart' => $cart, 'payment_methods' => $payment_methods, 'buy_method' => $request->type]);
        }
        // if(Auth::check() && !Auth::user()->admin) {
        //   // HERE
        // } elseif(Auth::check() && Auth::user()->admin) {
        //   return redirect()->route('home_page')->with(['alert' => [
        //     'type' => 'error',
        //     'title' => 'Thông Báo',
        //     'content' => 'Bạn không có quyền truy cập vào trang này!'
        //   ]]);
        // } else {
        //   return redirect()->route('login')->with(['alert' => [
        //     'type' => 'info',
        //     'title' => 'Thông Báo',
        //     'content' => 'Bạn hãy đăng nhập để mua hàng!'
        //   ]]);
        // }
    }

    public function payment(Request $request)
    {
        $payment_method = PaymentMethod::select('id', 'name')->where('id', $request->payment_method)->first();

        if (Str::contains($payment_method->name, 'COD')) {

            $order = $this->createOrder($request, 'COD');

            if ($request->buy_method == 'buy_now') {
                $this->createOrderDetail($order, $request, 'buy_now', 'COD');
            } elseif ($request->buy_method == 'buy_cart') {
                $this->createOrderDetail($order, $request, 'buy_cart', 'COD');
            }

            return redirect()->route('home_page')->with([
                'alert' => [
                    'type'    => 'success',
                    'title'   => 'Mua hàng thành công',
                    'content' => 'Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của chúng tôi. Sản phẩm của bạn sẽ được chuyển đến trong thời gian sớm nhất.'
                ]
            ]);

        } elseif (Str::contains($payment_method->name, 'VNPAY')) {

            $order = $this->createOrder($request, 'VNPAY');

            if ($request->buy_method == 'buy_now') {

                $order_details = $this->createOrderDetail($order, $request, 'buy_now', 'VNPAY');

                $amount = $order_details->price * $order_details->quantity;

                $vnp_Url = $this->createPayment($order, $amount, $request);

            } elseif ($request->buy_method == 'buy_cart') {

                $cart = Session::get('cart');

                $this->createOrderDetail($order, $request, 'buy_cart', 'VNPAY');

                $amount = $cart->totalPrice;

                $vnp_Url = $this->createPayment($order, $amount, $request);

                Session::forget('cart');
            }

            return redirect()->away($vnp_Url);
        }
    }

    private function createOrder($request, $payment)
    {
        $order = new Order;
        // $order->user_id           = 0;
        $order->payment_method_id = $request->payment_method;
        $order->order_code        = 'PSO' . str_pad(rand(0, pow(10, 5) - 1), 5, '0', STR_PAD_LEFT);
        $order->name              = $request->name;
        $order->email             = $request->email;
        $order->phone             = $request->phone;
        $order->address           = $request->address;

        if ($payment === 'COD') {
            $order->status = 1;
        } elseif ($payment === 'VNPAY') {
            $order->status = 0;
        }
        $order->save();
        return $order;
    }

    private function createOrderDetail($order, $request, $type, $payment)
    {
        if ($type === 'buy_now') {
            $order_details                    = new OrderDetail;
            $order_details->order_id          = $order->id;
            $order_details->product_detail_id = $request->product_id;
            $order_details->quantity          = $request->totalQty;
            $order_details->price             = $request->price;
            $order_details->save();

            if ($payment === 'COD') {
                $product           = ProductDetail::find($request->product_id);
                $product->quantity = $product->quantity - $request->totalQty;
                $product->save();
            }

        } elseif ($type === 'buy_cart') {
            $cart = Session::get('cart');
            foreach ($cart->items as $key => $item) {
                $order_details                    = new OrderDetail;
                $order_details->order_id          = $order->id;
                $order_details->product_detail_id = $item['item']->id;
                $order_details->quantity          = $item['qty'];
                $order_details->price             = $item['price'];
                $order_details->save();

                if ($payment === 'COD') {
                    $product           = ProductDetail::find($item['item']->id);
                    $product->quantity = $product->quantity - $item['qty'];
                    $product->save();
                }
            }
            Session::forget('cart');
        }
        return $order_details;
    }

    public function responsePayment(Request $request)
    {
        /**
         * Template
         * "vnp_Amount" => "1599000000"
         * "vnp_BankCode" => "NCB"
         * "vnp_BankTranNo" => "20210529180910"
         * "vnp_CardType" => "ATM"
         * "vnp_OrderInfo" => "Thanh toán đơn hàng Laptop StarLight"
         * "vnp_PayDate" => "20210529180851"
         * "vnp_ResponseCode" => "00"
         * "vnp_TmnCode" => "5DQOI6MP"
         * "vnp_TransactionNo" => "13514202"
         * "vnp_TxnRef" => "43"
         * "vnp_SecureHashType" => "SHA256"
         * "vnp_SecureHash" => "a8f88213a4ac8c50b2e460f9f63b88fedadf5c15821c6383ea32e7a7289f689d"
         */
        //vnp_SecureHash: Mã kiểm tra (checksum) để đảm bảo dữ liệu của giao dịch không bị thay đổi trong quá trình chuyển từ merchant sang VNPAY.
        // Việc tạo ra mã này phụ thuộc vào cấu hình của merchant và phiên bản api sử dụng.
        // Phiên bản hiện tại hỗ trợ SHA256 và MD5
        $mess           = 'Thanh toán thất bại!';
        $content        = 'Bạn đã hủy hoặc đã xẩy ra lỗi trong quá trình thanh toán!';
        $vnp_HashSecret = config('payment.vnpay.vnp_HashSecret');
        $vnp_SecureHash = $request->vnp_SecureHash;

        if (!$vnp_SecureHash || empty($vnp_SecureHash)) {
            return redirect()->route('home_page')->with([
                'alert' => [
                    'type'    => 'error',
                    'title'   => $mess,
                    'content' => $content
                ]
            ]);
        }
        $inputData = [];
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        unset($inputData['vnp_SecureHashType']);
        unset($inputData['vnp_SecureHash']);

        ksort($inputData);
        $i        = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . $key . "=" . $value;
            } else {
                $hashData = $hashData . $key . "=" . $value;
                $i        = 1;
            }
        }
        //$secureHash = md5($vnp_HashSecret . $hashData);
        $secureHash = hash('sha256', $vnp_HashSecret . $hashData);
        $order_id   = $inputData['vnp_TxnRef'];
        // Get Order
        if ($secureHash == $vnp_SecureHash && $_GET['vnp_ResponseCode'] == '00') {
            $mess    = 'Thanh toán thành công';
            $content = 'Cảm ơn bạn đã tin tưởng và lựa chọn chúng tôi';

            $order         = Order::query()->where('id', $order_id)->with('order_details')->first();
            $order->status = 1;
            $order->save();

            foreach ($order->order_details as $order_detail) {
                $product_detail           = ProductDetail::where('id', $order_detail->product_detail_id)->first();
                $product_detail->quantity = $product_detail->quantity - $order_detail->quantity;
                $product_detail->save();
            }
            return redirect()->route('home_page')->with([
                'alert' => [
                    'type'    => 'success',
                    'title'   => $mess,
                    'content' => $content
                ]
            ]);
        }
        return redirect()->route('home_page')->with([
            'alert' => [
                'type'    => 'error',
                'title'   => $mess,
                'content' => $content
            ]
        ]);
    }

    public function createPayment($order, $amount, $request)
    {
        $vnp_TmnCode    = config('payment.vnpay.vnp_TmnCode');
        $vnp_Returnurl  = config('payment.vnpay.vnp_Returnurl');
        $vnp_Url        = config('payment.vnpay.vnp_Url');
        $vnp_HashSecret = config('payment.vnpay.vnp_HashSecret');

        $vnp_TxnRef    = $order->id; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này sang VNPAY
        $vnp_OrderInfo = "Thanh toán đơn hàng " . config('app.name');
        $vnp_OrderType = 'billpayment';

        $vnp_Amount = $amount * 100;

        $vnp_Locale   = 'vn';
        $vnp_BankCode = $request->bank_code;
        $vnp_IpAddr   = $_SERVER['REMOTE_ADDR'];
        $inputData    = array(
            "vnp_Version"    => "2.0.0",
            "vnp_TmnCode"    => $vnp_TmnCode,
            "vnp_Amount"     => $vnp_Amount,
            "vnp_Command"    => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode"   => "VND",
            "vnp_IpAddr"     => $vnp_IpAddr,
            "vnp_Locale"     => $vnp_Locale,
            "vnp_OrderInfo"  => $vnp_OrderInfo,
            "vnp_OrderType"  => $vnp_OrderType,
            "vnp_ReturnUrl"  => $vnp_Returnurl,
            "vnp_TxnRef"     => $vnp_TxnRef,
        );
        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }
        ksort($inputData);
        $query    = "";
        $i        = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . $key . "=" . $value;
            } else {
                $hashdata .= $key . "=" . $value;
                $i        = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            // $vnpSecureHash = md5($vnp_HashSecret . $hashdata);
            $vnpSecureHash = hash('sha256', $vnp_HashSecret . $hashdata);
            $vnp_Url       .= 'vnp_SecureHashType=SHA256&vnp_SecureHash=' . $vnpSecureHash;
        }
        // Return URL
        return $vnp_Url;
        // Popup
        // $returnData = [
        //     'code' => '00', 'message' => 'success', 'data' => $vnp_Url
        // ];
        //
        // return $returnData;
    }

    public function ipn()
    {
        $vnp_SecureHash = $_GET['vnp_SecureHash'];
        if (!$vnp_SecureHash || empty($vnp_SecureHash))
            redirect(base_url());
        /*
         * IPN URL: Record payment results from VNPAY
         * Implementation steps:
         * Check checksum
         * Find transactions in the database
         * Check the status of transactions before updating
         * Check the amount of transactions before updating
         * Update results to Database
         * Return recorded results to VNPAY
         */

        $vnp_TmnCode    = config('payment.vnpay.vnp_TmnCode');
        $vnp_Returnurl  = config('payment.vnpay.vnp_Returnurl');
        $vnp_Url        = config('payment.vnpay.vnp_Url');
        $vnp_HashSecret = config('payment.vnpay.vnp_HashSecret');


        $inputData  = array();
        $returnData = array();
        $data       = $_REQUEST;
        foreach ($data as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHashType']);
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i        = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . $key . "=" . $value;
            } else {
                $hashData = $hashData . $key . "=" . $value;
                $i        = 1;
            }
        }
        $secureHash = hash('sha256', $vnp_HashSecret . $hashData);
        // $secureHash = md5($vnp_HashSecret . $hashData);
        // Payment status
        $Status = 0; // pending
        $code   = $inputData['vnp_TxnRef'];
        // Get Order
        $order = $this->order->getOrderByCode($code);
        $order = (array)$order;
        // Ex:
        // $sql = "SELECT * FROM `orders` WHERE `OrderId`=" . sql_escape($Id);
        // $result = $conn->query($sql);
        // $order = mysqli_fetch_assoc($result);
        //
        $vnp_Amount = $inputData['vnp_Amount'];
        $vnp_Amount = (int)$vnp_Amount / 100;
        try {
            // checksum
            if ($secureHash == $vnp_SecureHash) {
                // check OrderId
                if (isset($order["id"]) && $order["id"] != null) {
                    // check amount
                    if ($order['total'] != null && $order['total'] == $vnp_Amount) {
                        // check Status
                        if ($order["status"] != null && $order["status"] == 0) {
                            if ($inputData['vnp_ResponseCode'] == '00') {
                                $Status = 2; // Payment status success
                                // Here code update payment status success into your database
                                // ex:
                                // $update = "UPDATE `orders` SET `Status`='".sql_escape($Status)."' WHERE `OrderId`=" . sql_escape($Id);
                                $this->order->update($order['id'], array('status' => $Status));
                                $returnData['RspCode'] = '00';
                                $returnData['Message'] = 'Confirm Success';
                            } else {
                                $Status = -1; // Payment status fail
                                // Here code update payment status fail into your database
                                // ex:
                                // $update = "UPDATE `orders` SET `Status`='".sql_escape($Status)."' WHERE `OrderId`=" . sql_escape($Id);
                                $this->order->update($order['id'], array('status' => $Status));
                                $returnData['RspCode'] = '00';
                                $returnData['Message'] = 'Confirm Success';
                            }
                        } else {
                            $returnData['RspCode'] = '02';
                            $returnData['Message'] = 'Order already confirmed';
                        }
                    } else {
                        $returnData['RspCode'] = '04';
                        $returnData['Message'] = 'Invalid Amount';
                    }
                } else {
                    $returnData['RspCode'] = '01';
                    $returnData['Message'] = 'Order not found';
                }
            } else {
                $returnData['RspCode'] = '97';
                $returnData['Message'] = 'Chu ky khong hop le';
            }
        } catch (Exception $e) {
            $returnData['RspCode'] = '99';
            $returnData['Message'] = 'Unknow error';
        }
        echo json_encode($returnData);
    }

}
