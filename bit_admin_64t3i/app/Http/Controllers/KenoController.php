<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\KenoVol;

class KenoController extends Controller
{
    // 目标币种（忽略大小写）
    public $symbols = ['BTC', 'ETH', 'TRUMP', 'ETC', 'BABY', 'XRP', 'LTC', 'BCH', 'TRX', 'DOT', 'UNI', 'LINK', 'AI', 'DOGE', 'BNB', 'ADA', 'SOL', 'AAVE', 'CAKE', 'MANA'];
    
    public function test()
    {
        
    }
    
    //获取基诺号码接口
    public function index()
    {
        $date = date('Ymd');
        //根据当前时间计算期号，3分钟1期
        $startOfDay = strtotime("today");
        $now = time();
        $secondsPassed = $now - $startOfDay;
        $intervalsPassed = (int)floor($secondsPassed / 180);
        $intervalsPassed = (string)$date . (string)$intervalsPassed;
        
        $exists = KenoVol::where('no', $intervalsPassed)->exists();
        if(!$exists){
            $this->kenopull();
        }
        $keno = KenoVol::where('no', $intervalsPassed)->get();
        return response()->json($keno);
    }
    
    //数据统计接口
    public function kenocount()
    {
        $date = date('Ymd');
        //根据当前时间计算期号，3分钟1期
        $startOfDay = strtotime("today");
        $now = time();
        $secondsPassed = $now - $startOfDay;
        $intervalsPassed = (int)floor($secondsPassed / 180);
        $intervalsPassed = (string)$date . (string)$intervalsPassed;
        
        $exists = KenoVol::where('no', $intervalsPassed)->exists();
        if(!$exists){
            $this->kenopull();
        }
        $keno = KenoVol::where('no', $intervalsPassed)->get();
        return response()->json($keno);
    }
    
    public function kenopull()
    {   
        
        $huobidata = $this->huobidata();
        $biandata = $this->biandata();
        $keno = [];
        $kenotext = "";
        
        $date = date('Ymd');
        //根据当前时间计算期号，3分钟1期
        $startOfDay = strtotime("today");
        $now = time();
        $secondsPassed = $now - $startOfDay;
        $intervalsPassed = (int)floor($secondsPassed / 180);
        $intervalsPassed = (string)$date . (string)$intervalsPassed;
        
        //判断当前期号是否已经存储
        $exists = KenoVol::where('no', $intervalsPassed)->exists();
        if($exists){
            echo $intervalsPassed . '期已存在';
            exit;
        }
        
        foreach ($this->symbols as $target) {
                $hbamount = isset($huobidata[$target]) ? $huobidata[$target]['amount'] : 0;
                $baamount = isset($biandata[$target]) ? $biandata[$target]['amount'] : 0;
                $kenoamount = (string)($hbamount + $baamount);
                $kenoamount = str_replace('.', '', rtrim($kenoamount, '0'));
                $kenodata = $this->kenocom($kenoamount , 5 , $kenotext);
                $keno[] = [
                        'date'  => $date,
                        'name'  => $target . 'USDT',
                        'no'    => $intervalsPassed,
                        'bavol' => $baamount,
                        'hbvol' => $hbamount,
                        'basen' => $kenodata[1],
                        'keno'  => $kenodata[0],
                        'createtime'=>time()
                    ];
                    
                if($kenotext == ""){
                    $kenotext = $kenodata[0];
                }else{
                    $kenotext .= "," . $kenodata[0];
                }
            }
            
        //插入数据
        KenoVol::insert($keno);

        echo $intervalsPassed . '期采集成功';
        //return response()->json($keno);
    }
    //计算keno号码
    public function kenocom($keno, $num, $kenotext) {
        
        if(intval($keno) == 0){
            return [0, 0];
        }
        // 如果总交易额小于n位，尾号加0
        if (strlen($keno) < $num) {
            $keno = $keno . "0";
            return $this->kenocom($keno, $num, $kenotext);
        }
    
        // 取后n位，取模81
        $i = intval(substr($keno, -$num));
        $m = 81;
        $ca = $i % $m;
        $ca1 = (string)$ca;
        
        // 如果是个位数，例如5，返回05
        if (strlen($ca1) == 1) {
            $ca1 = "0" . $ca1;
        }
    
        // 如果重复或基诺号是00，则重新取
        if (strpos($kenotext, $ca1) !== false || $ca1 == "00") {
            return $this->kenocom($keno, $num + 1, $kenotext);
        }
        
        return [$ca1, substr($keno, -$num)];
    }
    //获取火币数据
    public function huobidata()
    {
        // 发送请求获取所有币种行情
        $response = Http::get('https://api.huobi.pro/market/tickers');

        if ($response->failed()) {
            return response()->json(['error' => '请求火币行情失败'], 500);
        }

        $data = $response->json();

        if (!isset($data['data']) || !is_array($data['data'])) {
            return response()->json(['error' => '数据格式错误'], 500);
        }

        // 筛选目标币种的 USDT 交易对
        $result = [];
        foreach ($data['data'] as $item) {
            if (!isset($item['symbol'])) continue;

            // 例：ethusdt，ltcusdt 等，取前缀
            $symbol = strtoupper($item['symbol']);

            foreach ($this->symbols as $target) {
                if ($symbol === $target . 'USDT') {
                    $result[$target] = [
                        'price' => $item['close'] ?? null,
                        'open'  => $item['open'] ?? null,
                        'high'  => $item['high'] ?? null,
                        'low'   => $item['low'] ?? null,
                        'amount' => $item['amount'] ?? null,
                    ];
                    break;
                }
            }
        }
        return $result;
    }
    //获取币安数据
    public function biandata()
    {

        // 发送请求到币安，获取所有交易对数据
        $response = Http::get('https://api.binance.com/api/v3/ticker/24hr');

        if ($response->failed()) {
            return response()->json(['error' => '请求币安行情失败'], 500);
        }

        $data = $response->json();

        if (!is_array($data)) {
            return response()->json(['error' => '返回数据格式错误'], 500);
        }

        // 筛选目标币种的 USDT 交易对
        $result = [];

        foreach ($data as $item) {
            if (!isset($item['symbol'])) continue;

            $symbol = strtoupper($item['symbol']);

            foreach ($this->symbols as $target) {
                if ($symbol === $target . 'USDT') {
                    $result[$target] = [
                        'price' => $item['lastPrice'] ?? null,
                        'open'  => $item['openPrice'] ?? null,
                        'high'  => $item['highPrice'] ?? null,
                        'low'   => $item['lowPrice'] ?? null,
                        'amount' => $item['volume'] ?? null
                    ];
                    break;
                }
            }
        }
        return $result;
    }
}
