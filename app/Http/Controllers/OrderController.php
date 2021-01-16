<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Order;
use App\Model\Product;
use App\Model\Tenday;
use Validator;
use Carbon\Carbon;
use Mail;
class OrderController extends Controller
{
    public function order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customername' => 'required','product_id' => 'required',
            'quantity' => 'required'
        ]);
    	if ($validator->passes())
        {
            $product=Product::find($request->product_id);
            if($product->available>=$request->quantity)
            {
            $order=new Order;
            $order->customername=$request->customername;
            $order->product_id=$request->product_id;
            $order->quantity=$request->quantity;
            $order->save();
            $product->available=$product->available-$request->quantity;
            $product->save();
            //notification for availability less than 3
                if($product->available<3)
                {
                    Mail::send('mail',compact('product'), function($message) {
                        $message->to('contact@subdine.com')->subject
                           ('Availability Notification');
                           $message->from('dhinese.wicmad@gmail.com','Subdine');
                     });
                }
            return response( array( "message" => "Dishes Ordered Successfully")); 
            }
            return response( array( "message" => "The no. of stocks you needed is  not available")); 
        }
        return response()->json(['error'=>$validator->errors()->all()]);         
    }
    public function soldcount()
    {
        $sold=0;
        $order=Order::whereDate('created_at','>=',Carbon::today()->subdays(2))->get();
        foreach($order as $order)
        $sold=$order->quantity+$sold;
        return response(array('last 2 days sold count'=>$sold)); 
    }   
    public function leastmost()
    {
        $tenday=Tenday::truncate();
        $product=Product::all();
        foreach($product as $product)
        {
            $productorder=Order::whereDate('created_at','>=',Carbon::today()->subdays(10))->where('product_id',$product->id)->sum('quantity');
            $tenday=new Tenday;
            $tenday->product_id=$product->id;
            $tenday->quantity=$productorder;
            $tenday->save();
        }
        $least=Tenday::orderBy('quantity','asc')->take(5)->get();
        $most=Tenday::orderBy('quantity','desc')->take(5)->get();
        return response(array('last 10 days least sold '=>$least,'last 10 days most sold '=>$most)); 
    }
}