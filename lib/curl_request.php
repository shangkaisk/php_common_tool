<?php
class curl_request
{
	private $post_data,$url,$header;
	private $timeout = 3;
	private $user_agent= 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.153 Safari/537.36';
	private $ch,$http_code,$http_status;
	private $http_method;
	private $header_return;
	private $follow_location = 1;
	const HTTP_GET = 1,HTTP_POST = 2,HTTP_HEAD = 3,HTTP_DELETE = 4;
	function __construct()
	{
		$this->ch = curl_init();
	}
	public  function  set_timeout ($timeout)
	{
		$this->timeout = $timeout;
	}
	public  function  set_http_method ($http_method)
	{
		$this->http_method = $http_method;
	}
	public function set_post_data($post_data)
	{
		$this->post_data = $post_data;
	}
	public function set_request_url($url)
	{
		$this->url = $url;
	}
    function set_header ($header)
    {
        $this->header = $header;
    }
	function set_header_return($header_return)
	{
		$this->header_return = $header_return;
	}
	function set_follow_location($follow_location)
	{
		$this->follow_location = $follow_location;
	}

	/**
	 * 默认使用post提交
	 * @return 网络数据
	 */
	public function start()
	{
		curl_setopt($this->ch, CURLOPT_URL, $this->url);
		
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_USERAGENT,$this->user_agent );
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		//301跳转则 获取真实内容
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION,$this->follow_location);
		curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0); //强制协议为1.0
		//curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("'Expect: '")); //头部要送出'Expect: '
		//curl_setopt($this->ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 ); //强制使用IPV4协议解析域名
		
		if($this->http_method == self::HTTP_GET)
		{
			//curl_setopt($this->ch, CURLOPT_GET, 1);//GET提交方式
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
		}else if($this->http_method == self::HTTP_HEAD)//head访问，仅获取http头
		{
			curl_setopt($this->ch, CURLOPT_NOBODY, true);
			curl_setopt($this->ch, CURLOPT_HEADER, true);
		}
        else if($this->http_method == self::HTTP_DELETE)//delete访问，
        {
            curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($this->ch, CURLOPT_HEADER, true);
        }

        else
		{
			curl_setopt($this->ch, CURLOPT_POST, 1);//post提交方式

			if($this->post_data ==null)//
			{
				$this->post_data = array();
			}
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->post_data);

		}

        /**
         * CURLOPT_HTTPHEADER header中的cookie是无效的，要单独设置
         */
        if ($this->header['Cookie'])
        {
            curl_setopt($this->ch, CURLOPT_COOKIE, $this->header['Cookie']);
        }
        unset ($this->header['Cookie']);

        if($this->header)
        {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->header);
        }
		if(header_return){
		curl_setopt($this->ch, CURLOPT_HEADER, $this->header_return);
		}


		curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
		$output = curl_exec($this->ch);
		$this->set_request_status($this->ch);
		
		//var_dump($http_status,$this->http_code);
		//curl_close($this->ch);
		return  $output;	
		
	}

	
	private  function set_request_status($ch)
	{
		//echo "<br/>-------------------------------<br/>";
		$this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		//var_dump($this->http_code);
		if($this->http_code && $this->http_code<400)
		{
			$this->http_status = true;
		}else 
		{
			$this->http_status = false;
		}
		
		return $this->http_status ;
	}
	public function  get_total_time ()
	{
		$info = curl_getinfo($this->ch);
		//echo '获取'. $info['url'] . '耗时'. $info['total_time'] . '秒';
		return $info['total_time'];
	}
	public function get_request_status ()
	{
		return $this->http_status;
	}
	public function get_error_msg ()
	{
		return curl_error($this->ch);
	}
	public function get_http_code ()
	{
		return $this->http_code;
	}
	
	
	
}