<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Validator;
use Exception;

class HeraController extends Controller
{
	// 验证卡密
    public function verify(Request $request) {
    	$validator = Validator::make($request->all(), [
            'key'  => 'required|string',
            'mac'  => 'required|string',
            'hash' => 'required|string'
        ]);

        if ($validator->fails()) {
            return 0;
        }

        $key = $request->key;
        $mac = $request->mac;
        $hash = $request->hash;
        // 验证key,mac,hash是否匹配
        if (md5('KD' . $key . '_' . $mac . 'kd' ) !== $hash) {
        	return 0;
        }
        // 验证key是否存在且未被激活
        $whereArr = [
        				['key', '=', $key], 
        				['activeTime', '=', '-1']
        			];
        $keyExist = DB::table('key')->select('*')->where($whereArr)->count();
        if (!$keyExist) {
        	return 0;
        }
        // 更新key状态
        $updateArr = [
        				'activeTime' => time(),
        				'mac' => $mac
        			 ];
        DB::table('key')->where($whereArr)->update($updateArr);
        // 生成cfg字符串并返回
        $uuid1 = $this->uuid();
        $uuid2 = $this->uuid();
        $str = '';
        for ($i=0; $i<16; $i++) {
        	$str .= $uuid1[$i];
        	$str .= $key[$i];
        	$str .= $uuid2[$i];
        }
        $cfg = strval(rand(0,9)) . $str . $mac . strval(time()) . $this->uuid() . '=';
        echo $cfg;
    }

    // 生成卡密
    public function generate(Request $request) {
    	$validator = Validator::make($request->all(), [
    		'type'   => 'required|string',
    		'number' => 'required|integer|between:1,50',
    		'hash'   => 'required|string'
    	]);

    	if($validator->fails()) {
    		header('HTTP/1.1 401 Unauthorized');
			header('WWW-Authenticate: Basic realm="登录"');
			exit;  
    	}

    	// 判断
    	$type = $request->type;
    	$number = $request->number;
    	$hash = $request->hash;
    	// 假冒401
    	if (!in_array($type, ['day', 'week', 'month'])) {
    		header('HTTP/1.1 401 Unauthorized');
			header('WWW-Authenticate: Basic realm="登录"');
			exit;  
    	}
    	// 判断hash值
    	$dbHash = DB::table('hash')->select('hash')->first();
    	if ($dbHash->hash !== $hash) {
    		header('HTTP/1.1 401 Unauthorized');
			header('WWW-Authenticate: Basic realm="登录"');
			exit;	
    	}

    	// 开始生成卡密
    	$keys = [];
    	$timesMap = ['day'=>24*3600, 'week'=>7*24*3600, 'month'=>31*24*3600];
    	$times = $timesMap[$type];
    	for($i=0; $i<$number; $i++) {
    		$keys[] = [
    			'key'   => $this->uuid(),
    			'times' => $times
    		];
    	}
    	DB::table('key')->insert($keys);
    	return view('generate', ['keys'=>$keys]);
    }

    public function hera(Request $request) {
    	// 绕过hera原生的验证方式,感觉是凭这个字符串判断是否有更新
    	if (isset($request->md5) && isset($request->edl) && isset($request->hash)) {
			$md5 = $request->md5;
			$edl = $request->edl;
			$hash = $request->hash;
			echo "3980f997e36d6abbab4efc442bd076c8";
		}
		// 伪造卡密的验证结果
		if (isset($request->hash) && isset($request->hwid) && isset($request->info) && 
			!isset($request->pdl) && !isset($request->n)) {
			$hash = $request->hash;
			$hwid = $request->hwid;
			$info = $request->info;
			// 从hash中取出mac地址进行验证
			try {
				$mac = substr($hash, 49, 12);
				$str = substr($hash, 1, 48);
				$key = '';
				for($i=1; $i<48; $i+=3) {
					$key .= $str[$i];
				}
				// 验证mac与key是否匹配
				$whereArr = [
					['key', '=', $key],
					['mac', '=', $mac]
				];
				$keyMap2Mac = DB::table('key')->select('*')->where($whereArr)->count();
				if ($keyMap2Mac) {
					$data = [
						"sl" => false,
						"l_state" => "good",
						"key" => "pava_rs2@gmail.com:51dd8126918817c8976b98f43ce582f3",
						"cid" => "cf-pubg",
						"msg" => "Loaded",
						"target" => "TslGame.exe",
						"game_id" => 1,
						"ptr" => 68735816
					];
					return response()->json($data);
				} else {
					return response()->json(['msg'=>'HWID Missmatch, please reset.', 'l_state'=>'reset']);	
				}
			} catch(Exception $e) {
				return response()->json(['msg'=>'HWID Missmatch, please reset.', 'l_state'=>'reset']);
			}
		}

		// 伪造下载请求
		if (isset($request->hash) && isset($request->hwid) && isset($request->info) && 
			isset($request->pdl) && isset($request->n)) {
			$hash = $request->hash;
			try {
				$mac = substr($hash, 49, 12);
				$str = substr($hash, 1, 48);
				$key = '';
				for($i=1; $i<48; $i+=3) {
					$key .= $str[$i];
				}
				// 验证mac与key是否匹配
				$whereArr = [
					['key', '=', $key],
					['mac', '=', $mac]
				];
				$keyMap2Mac = DB::table('key')->select('*')->where($whereArr)->count();
				if ($keyMap2Mac) {
					header('Content-Disposition: attachment; filename="ezresource"');
					header('Vary: Accept-Encoding,User-Agent');
					header('Keep-Alive: timeout=5');
					header('Connection: Keep-Alive');
					header('Content-Type: application/octet-stream');
					$fileSize = filesize('./assets/ezresource');
					echo fread(fopen('./assets/ezresource', 'r'), $fileSize);
				} else {
					return 0;
				}
			} catch(Exception $e) {
				return 0;
			}
		}
		
    }

    public function uuid(){
		mt_srand((double)microtime()*10000); 
		$uuid = substr(strtoupper(md5(uniqid(rand(), true))), 0, 16);
		return $uuid;
    }


}
