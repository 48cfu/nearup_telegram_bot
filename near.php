<?

namespace Near;

Class NearData
{
    public static function GetNearRpcData($method)
    {

        $message = json_encode(array('jsonrpc' => '2.0', 'id' => "dontcare", 'method' => $method, 'params' => [null]));
        $requestHeaders = ['Content-type: application/json'];

        $ch = curl_init("https://rpc.betanet.nearprotocol.com");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);

        $json = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($json, true);
        return $data;
    }
}

Class NearView
{
    public static function FormatValidators($validators)
    {
        usort($validators, function ($a, $b) {
            return strcmp($a["account_id"], $b["account_id"]);
        });

        $reply = "";
        $i = 1;
        $alertFound = false;
        $validatorPositionsQuantity = strlen(count($validators));

        foreach($validators as $validator){
            $alert =  "";
            if($validator["num_produced_blocks"] < ($validator["num_expected_blocks"] * 0.9)){
                $alert =  " ⚠️ (".$validator["num_produced_blocks"]."/".$validator["num_expected_blocks"].")";
                $alertFound = true;
            }
            $reply .= (str_pad($i++, $validatorPositionsQuantity, "0", STR_PAD_LEFT)).". ".$validator["account_id"].": ".(round($validator["stake"]/1000000000000000000000000))." NEAR".$alert.chr(10);
        }

        if($alertFound)
            $reply .= "Legend: ⚠️ - kickout risk (produced blocks / expected blocks)";

        return $reply;
    }
}