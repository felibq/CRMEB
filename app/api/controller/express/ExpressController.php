<?php
namespace app\api\controller\express;

use app\Request;
use crmeb\services\UtilService;
use think\facade\Db;

class ExpressController{

    public function search(Request $request)
    {
        list($no, $type) = UtilService::postMore([
            ['no',''],
            ['type','']
        ],$request,true);

        $shipperCode=Db::table('eb_express')->where('name',$type)->value('code');
        $LogisticCode=$no;

        $kdnjson= $this->getOrderTracesByJson($shipperCode,$LogisticCode);
	   $kdnarr=json_decode($kdnjson, true);
	   
	   $arr=array();
	   if($kdnarr['Success']==true)
	   {
	   		
			if(@$kdnarr['Reason']=="暂无轨迹信息")
			{
				$arr['status']="205";
				$arr['msg']="没有信息";
				$arr['result']="";	
			}
			else
			{				
				$arr['status']="0";
				$arr['msg']="ok";
				$arr['result']['number']=$kdnarr['LogisticCode'];
				$arr['result']['type']=$kdnarr['ShipperCode'];
				$arr['result']['list']=array_reverse($kdnarr['Traces']);
				$arr['result']['deliverystatus']=$kdnarr['State'];
				$arr['result']['issign']="0";
				$arr['result']['expName']="0";
				$arr['result']['expSite']="0";
				$arr['result']['expPhone']="0";
				$arr['result']['courier']="";
				$arr['result']['courierPhone']="";
			}
					
	   }
	   else if(strpos($kdnarr['Reason'],"物流公司编号不正确") > 0)
		{
			$arr['status']="203";
			$arr['msg']="快递公司不存在";
			$arr['result']="";		
		}

	   $json=json_encode($arr,JSON_UNESCAPED_UNICODE);
	   $json=str_replace("AcceptStation","status",$json);
	   $json=str_replace("AcceptTime","time",$json);
	   return $json;
    }

    /**
		 * Json方式 查询订单物流轨迹
		 */
	public function getOrderTracesByJson($shipperCode,$LogisticCode){
		$requestData= "{'OrderCode':'','ShipperCode':'".$shipperCode."','LogisticCode':'".$LogisticCode."'}";
			
		$datas = array(
			'EBusinessID' => '1591933',
			'RequestType' => '1002',
			'RequestData' => urlencode($requestData) ,
			'DataType' => '2',
		);
		$datas['DataSign'] = self::encrypt($requestData, 'ee1722ac-e88c-4277-b2cc-402ec32d7c09');
		$result=self::sendPost('http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx', $datas);	
			
			//根据公司业务处理返回的信息......
			
			return $result;
		}
		/**
		 *  post提交数据 
		 * @param  string $url 请求Url
		 * @param  array $datas 提交的数据 
		 * @return url响应返回的html
		 */
		public static function sendPost($url, $datas) {
			$temps = array();	
			foreach ($datas as $key => $value) {
				$temps[] = sprintf('%s=%s', $key, $value);		
			}	
			$post_data = implode('&', $temps);
			$url_info = parse_url($url);
			if(empty($url_info['port']))
			{
				$url_info['port']=80;	
			}
			$httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
			$httpheader.= "Host:" . $url_info['host'] . "\r\n";
			$httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
			$httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
			$httpheader.= "Connection:close\r\n\r\n";
			$httpheader.= $post_data;
			$fd = fsockopen($url_info['host'], $url_info['port']);
			fwrite($fd, $httpheader);
			$gets = "";
			$headerFlag = true;
			while (!feof($fd)) {
				if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
					break;
				}
			}
			while (!feof($fd)) {
				$gets.= fread($fd, 128);
			}
			fclose($fd);  
			
			return $gets;
		}
		
		/**
		 * 电商Sign签名生成
		 * @param data 内容   
		 * @param appkey Appkey
		 * @return DataSign签名
		 */
		public static function encrypt($data, $appkey) {
			return urlencode(base64_encode(md5($data.$appkey)));
		}
}