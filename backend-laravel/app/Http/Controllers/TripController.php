<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Path;
use App\Models\Trip;
use App\Models\PaymentDetails;
use Auth;
use Stripe;
class TripController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/organization/trips",
     *      operationId="create a trip",
     *      tags={"Trips"},
     *      summary="create a trip",
     *      description="create a trip",
     *      @OA\Parameter(
     *          name="path_name",
     *          description="path name",
     *          required=true,
     *          in="path"
     *      ),
     *      @OA\Parameter(
     *          name="repitition",
     *          description="repitition",
     *          required=true,
     *          in="path"
     *      ),
     *      @OA\Parameter(
     *          name="date",
     *          description="date",
     *          required=true,
     *          in="path"
     *      ),
     *      @OA\Parameter(
     *          name="time",
     *          description="time",
     *          required=true,
     *          in="path"
     *      ),
     *      @OA\Parameter(
     *          name="num_seats",
     *          description="number of seats",
     *          required=true,
     *          in="path"
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation"
     *       ),
     *       @OA\Response(response=400, description="Bad request"),
     *       security={
     *           {"api_key_security_example": {}}
     *       }
     *     )
     *
     * Returns status of the update
     */
    public function create_trip(Request $req) {
        $validator = Validator::make($req->all(),[
            'path_name' => 'required|string',
            'repitition' => 'required|string', // one-time.
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'num_seats' => 'required'
        ]);
        $path = Path::where('name', $req->path_name)->first();

        if($validator->fails()) {
            $message = [];
            $message = UserController::format_message($message, $validator);
            return response([
                'status' => false,
                'message' => $message
            ], 200);
        }
        $organization = Auth::user()->organization;
        if($req->repitition == "one-time") {
            Trip::create([
                'path_id' => $path->id,
                'organization_id' => $organization->id,
                'repitition' => $req->repitition,
                'date' => $req->date,
                'time' => $req->time,
                'status' => 0,
                'price' => $path->price,
                'num_seats' => $req->num_seats
            ]);
            return response([
                'status' => true,
                'message' => ['trip is added successfully']
            ], 200);
        } else {

            return response([
                'status' => false,
                'message' => ['repitition is either one-time, daily, weekly']
            ], 200);
        }
    }
    /**
     * @OA\Get(
     *      path="/api/organization/trips",
     *      operationId="get all trips",
     *      tags={"Trips"},
     *      summary="get all trips",
     *      description="get all trips",
     *      @OA\Response(
     *          response=200,
     *          description="successful operation"
     *       ),
     *       @OA\Response(response=400, description="Bad request"),
     *       security={
     *           {"api_key_security_example": {}}
     *       }
     *     )
     *
     * Returns status of the update
     */
    public function get_all_trips(Request $req) {
        $all_trips = Trip::get();
        $trips = [];
        foreach($all_trips as $trip) {
            $trip1 = [];
            $trip1["id"] = $trip->id;
            $trip1["path_name"] = $trip->path->name;
            $trip1["route"] = [
                'source' => $trip->path->route->source,
                'destination' => $trip->path->route->destination
            ];
            $all_stops = $trip->path->stops->toArray();
            $trip1["stops"] = [];
            foreach($all_stops as $stop) {
                $stop1 = [
                    'name' => $stop["name"],
                    'longitude' => $stop["longitude"],
                    'latitude' => $stop["latitude"]
                ];
                $trip1["stops"][] = $stop1;
            }
            $trip1["repitition"] = $trip->repitition;
            $trip1["date"] = $trip->date;
            $trip1["time"] = $trip->time;
            $trip1["status"] = $trip->status;
            $trip1["path_distance"] = $trip->path->distance;
            $trip1["path_time"] = $trip->path->time;
            $trip1["price"] = $trip->price;
            $trips[] = $trip1;
        }
        return response([
            'status' => true,
            'message' => $trips
        ]);
    }
    /**
     * @OA\Post(
     *      path="/api/organization/trips/pay/{id}",
     *      operationId="pay a trip",
     *      tags={"Trips"},
     *      summary="pay a trip",
     *      description="pay a trip",
     *      @OA\Parameter(
     *          name="payment_method",
     *          description="payment method (wallet or credit)",
     *          required=true,
     *          in="path"
     *      ),
     *      @OA\Parameter(
     *          name="card_number",
     *          description="card number",
     *          required=true,
     *          in="path"
     *      ),
     *      @OA\Parameter(
     *          name="exp_month",
     *          description="expiration month",
     *          required=true,
     *          in="path"
     *      ),
     *      @OA\Parameter(
     *          name="exp_year",
     *          description="expiration year",
     *          required=true,
     *          in="path"
     *      ),
     *      @OA\Parameter(
     *          name="CVC",
     *          description="CVC (three-digit number)",
     *          required=true,
     *          in="path"
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation"
     *       ),
     *       @OA\Response(response=400, description="Bad request"),
     *       security={
     *           {"api_key_security_example": {}}
     *       }
     *     )
     *
     * Returns status of the update
     */
    public function pay_trip(Request $req) {
        $trip = Trip::find($req->id);
        /*if($trip->status == 1) {
            return response([
                'status' => false,
                'message' => ['trip is already paid']
            ], 200);
        }*/
        if($req->payment_method && $req->payment_method == "credit") {
            $validator = Validator::make($req->all(), [
                'card_number' => 'required|size:16',
                'exp_month' => 'required|size:2',
                'exp_year' => 'required|size:4',
                'CVC' => 'required|size:3'
            ]);
            if($validator->fails()) {
                $message = [];
                $message = UserController::format_message($message, $validator);
                return response([
                    'status' => false,
                    'message' => $message
                ], 200);
            }
            
            Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
            
            $name = Auth::user()->organization->name;
            try {
                $result = Stripe\Token::create([
                    "card" => [
                        "name" => $req->name,
                        "number" => $req->card_number,
                        "exp_month" => $req->exp_month,
                        "exp_year" => $req->exp_year,
                        "cvc" => $req->CVC
                    ]
                ]);
            } catch(\Exception $e) {
                return response([
                    'status' => false,
                    'message' => [$e->getError()->message]
                ], 200);
            }
            $token = $result['id'];
            try{
                $status = Stripe\Charge::create([
                    "amount" => $trip->price * 100,
                    "currency" => "egp",
                    "card" => $token,
                    "description" => "Trip " . $trip->id . " payment" 
                ]);
            } catch(\Exception $e) {
                return response([
                    'status' => false,
                    'message' => [$e->getError()->message]
                ], 200);
            }
            $trip->status = 1;
            $trip->save();
            PaymentDetails::create([
                'trip_id' => $trip->id,
                'amount' => $trip->price,
                'currency' => "egp"
            ]);
            return response([
                'status' => true,
                'message' => ['trip is paid succesfully']
            ], 200);
        } elseif(!$req->payment_method) {
            return response([
                'status' => false,
                'message' => ['The payment method is required']
            ], 200);
        } elseif($req->payment_method == "wallet") {
            $wallet = Auth::user()->organization->wallet;
            if($trip->price > $wallet->balance) {
                return [
                    'status' => false,
                    'message' => ['balance of the wallet isn\'t enough']
                ];
            } else {
                $wallet->balance = $wallet->balance - $trip->price;
                $wallet->save();
                return response([
                    'status' => true,
                    'message' => ['trip is paid succesfully, your balance now is ' . $wallet->balance]
                ], 200);
            }
        } else {

            return response([
                'status' => false,
                'message' => ['The payment method is either wallet or credit']
            ], 200);
        }
    }
}
