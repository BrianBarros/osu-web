<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Admin\Controller;
use App\Models\Store;
use Request;

class OrdersController extends Controller
{
    protected $section = 'admin-store';
    protected $actionPrefix = 'orders-';

    public function index()
    {
        return $this->show();
    }

    public function show($orderId = null)
    {
        $orders = Store\Order::with('user', 'address', 'address.country', 'items.product');

        if ($orderId) {
            $orders->where('orders.order_id', $orderId);
        } else {
            $orders->where('orders.status', 'paid');
        }

        $ordersItemsQuantities = Store\Order::itemsQuantities($orders);

        $orders = $orders->orderBy('created_at')->get();

        $productId = (int) Request::input('product');
        if ($productId) {
            $orders = array_where($orders, function ($_i, $order) use ($productId) {
                return $order->items()->where('product_id', $productId)->exists();
            });
        }

        return view('admin.store.orders.show', compact('orders', 'ordersItemsQuantities'));
    }

    public function ship()
    {
        $order = Store\Order::where('status', 'paid')
            ->where('tracking_code', 'like', 'EJ%')
            ->get();

        foreach ($order as $o) {
            $o->status = 'shipped';
            $o->save();
        }

        return ujs_redirect(route('admin.store.orders.index'));
    }

    public function update($id)
    {
        $order = Store\Order::findOrFail($id);

        if ($order->status !== 'paid') {
            return error_popup("order status {$order->status} is invalid.");
        }

        $order->unguard();
        $order->update(Request::input('order'));
        $order->save();

        return ['message' => "order {$id} updated"];
    }
}
