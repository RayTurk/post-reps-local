<?php

namespace App\Http\Middleware;

use App\Models\Order;
use Closure;
use Illuminate\Http\Request;
use App\Services\OrderService;

class EnsureOrderIsCompleted
{

    protected $orderService;

    public function __construct(
        OrderService $orderService
    ) {
        $this->orderService = $orderService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        switch ($request->type) {
            case 'install':
                $order = $this->orderService->findById($request->id);
                break;

            case 'repair':
                $order = $this->orderService->findRepairOrderById($request->id);
                break;

            case 'removal':
                $order = $this->orderService->findRemovalOrderById($request->id);
                break;

            case 'delivery':
                $order = $this->orderService->findDeliveryOrderById($request->id);
                break;

            default:
                return redirect('/');
                break;
        }

        if (!$order) { // There is no order.
            return redirect('/');
        }

        if ( $order->rating && $order->feedback && $order->feedback_date) { // Order completed and rated.
            session()->flash('success', 'Your feedback was submitted successfully. Thank you.');
            return $next($request);
        }

        if (!($order->rating && $order->feedback && $order->feedback_date) && $order->status == Order::STATUS_COMPLETED) { //Order completed but not rated.
            return $next($request);
        }

        return redirect('/');
    }
}
