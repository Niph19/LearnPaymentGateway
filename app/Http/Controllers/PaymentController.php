<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view('demo-payment', [
            'title' => 'Demo Payment',
            'order' => $request->session()->get('demo_order'),
        ]);
    }


    public function pay(Request $request)
    {
        $order = $request->session()->get('demo_order');

        if($order) {
            if($order['status'] === 'paid'){
                return to_route('demo-payment.index');
            }
            return redirect()->away($order['payment_url']);
        }

        $orderCode = 'Demo-' . Str::uuid();
        $amount = 10000;

        try{
            $this->configMidtrans();
            $payment = Snap::createTransaction([
                'transaction_details' => [
                    'order_id' => $orderCode,
                    'gross_amount' => $amount,
                ]
            ]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $request->session()->put('demo_order', [
            'code' => $orderCode,
            'amount' => $amount,
            'status' => 'pending',
            'payment_url' => $payment->redirect_url,
        ]);

        return redirect()->away($payment->redirect_url);
    }

    public function check(Request $request)
    {
        $order = $request->session()->get('demo_order');

        if(!$order) {
            return to_route('demo-payment.index')->with('message', 'Buat transaksi terlebih dahulu.');
        }

        try{
            $this->configMidtrans();
            $result = Transaction::status($order['code']);
        } catch (\Exception $e) {
            report($e);
            return to_route('demo-payment.index')->with('message', "Status belum bisa diperiksa. Pastikan metode bayar sudah dipilih di snap");
        }

        $sameOrder = $result->order_id === $order['code'];
        $sameAmount = (int) $result->gross_amount === (int) $order['amount'];

        if (!$sameOrder || !$sameAmount) {
            return to_route('demo-payment.index')->with('message', 'Status tidak valid. Pastikan metode bayar sudah dipilih di Snap.');
        }

        $midtransStatus = strtolower((string) ($result->transaction_status ?? 'unknown'));
        if ($midtransStatus === 'settlement' || $midtransStatus === 'capture') {
            $order['status'] = 'paid';
            $request->session()->put('demo_order', $order);
            return to_route('demo-payment.index')->with('message', 'Transaksi berhasil.');
        }

        return to_route('demo-payment.index')->with('message', 'Pembayaran belum selesai. Silakan lanjutkan proses pembayaran di Midtrans.');
    }

    private function configMidtrans(): void
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = false;
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
