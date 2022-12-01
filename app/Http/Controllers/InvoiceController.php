<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Config;

class InvoiceController extends Controller
{
    private $customer;
    private $product;
    private $account;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $invoices = Invoice::all();

        //return $invoice;
        return view('invoices.index', compact('invoices')); 
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //Not being Used as creating comes directly from poster webhook.
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {        
        //Load data in json format from hook.
        $postData = $request->json()->all();

        $verify_original = $postData['verify'];
        unset($postData['verify']);

        $verify = [
            $postData['account'],
            $postData['object'],
            $postData['object_id'],
            $postData['action'],
        ];

        // Check for additional data
        if (isset($postData['data'])) {
            $verify[] = $postData['data'];
        }
        $verify[] = $postData['time'];
        $verify[] = config('poster.secret');

        // Check for data verification
        $verify = md5(implode(';', $verify));
        $account = $postData['account'];
        
        // Remove on production
        $fp = fopen('poster.log', 'a');

        /*if ($verify != $verify_original) {

            echo json_encode(['status' => 'reject']);
            // Local Dev Logging, remove on production.
            fwrite($fp, "Webhook rejected -> ".date('Y-m-d H:i:s')." | verify: " .$verify." | v-original: " .$verify_original."\n");
            exit;
        }*/
        
        // Local Dev Logging, remove on production.
        fwrite($fp, "Webhook From Poster -> ".date('Y-m-d H:i:s')." | object: " .$postData['object']." | action: " .$postData['action']." | JsonDATA: ". json_encode($postData) ."\n");

        if ($postData['object'] == 'transaction' && $postData['action'] == 'closed') {
            //It's a poster sale transaction object hook with a closed action, we grab it and proceed to pick extra info regarding product and customer.
            //fwrite($fp, "data -> " .$postData['data']." | transactions_history: " .$postData['data']['transactions_history']."\n");            
            
            $data = json_decode($postData['data'], true);
            $transaction = $data['transactions_history'];
            $data2 = json_decode($transaction['value_text'], true);

            $time = $postData['time'];
            $price = substr($transaction['value2'], 0, -2);
                      
            $productsd = $data2['products'];
            $products = array();

            foreach ($productsd as $productd) {
                $url = 'https://joinposter.com/api/menu.getProduct'
                . '?token='.config('poster.token')
                . '&product_id='.$productd['id'];

                $response2 = json_decode(Http::acceptJson()->get($url),true);
                $response = $response2['response'];

                $product = array(
                    'id' => $response['product_id'],
                    'name' => $response['product_name'],
                    'count' => $productd['count'],
                    'price' => substr($response['price'][1], 0, -2),
                    'tax_id' => $response['tax_id']
                );
                $products[] = $product;
                
            }
            //
            
            $invoice = new Invoice;
            $invoice->account = $account;
            $invoice->products = json_encode($products);
            $invoice->time = $time;
            $invoice->price = $price;
     
            $invoice->save();
                 
        }
        if ($postData['object'] == 'client_payed_sum' && $postData['action'] == 'changed') {
            $url = 'https://joinposter.com/api/clients.getClient'
            . '?token='.config('poster.token')
            . '&client_id='.$postData['object_id'];

            $response2 = json_decode(Http::acceptJson()->get($url),true);
            $response = $response2['response'][0];

            
            Invoice::where('time', $postData['time'])
            ->where('account', $account)
            ->update(['name' => $response['firstname'].' '.$response['lastname'],
            'phone_number' => $response['phone_number'],
            'email' => $response['email'],
            'card_number' => $response['card_number'],
            'address' => $response['address'],
            ]);

        }
        // Sending response back to Poster. Otherwise Poster will attempt send webhook for 15 times
        echo json_encode(['status' => 'accept']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function show(Invoice $invoice)
    {
        //TOdo
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function edit(Invoice $invoice)
    {
        //TOdo
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $request->validate([
            'account'=>'required',
            'products'=>'required',
            'time'=>'required',
            'name'=>'required',
            'price'=>'required|max:10|regex:/^-?[0-9]+(?:\.[0-9]{1,2})?$/'
        ]);
        
        $invoice = Invoice::find($id);
        $invoice->name =  $request->get('name');
        $invoice->products = $request->get('products');
        $invoice->time = $request->get('time');
        $invoice->price = $request->get('price');
        $invoice->save();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $invoice = Invoice::find($id);
        $invoice->delete();
    }
}
