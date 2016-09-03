<?php
/*
Copyright (c) 2013 Alexander Kirk
http://alexander.kirk.at/

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

class RateExceededException extends Exception {}

class RateLimiter {
	private $prefix, $memcache , $keysvisited;
	// how long should we keep memcache entries
	public $maxMinutes=10;

	public function __construct(Memcache $memcache, $ip, $prefix = "rate") {
		$this->memcache = $memcache;
		if (!$memcache) {
			echo "Problem connecting to memcache server";
			exit;
		}
		$this->prefix = $prefix . $ip;
		$keysvisited=array();
	}

	public function limitRequestsInMinutes($allowedRequests, $minutes) {
		$requests = 0;

		foreach ($this->getKeys($minutes) as $key) {
			$requestsInCurrentMinute = $this->memcache->get($key);

			// if the key is read for a second or third tim in the same 
			// php execution, we remove the previous additions so that the 
			// last call reports correct numbers
			if ($this->keysvisited[$key]) {
				$requestsInCurrentMinute-=$this->keysvisited[$key];
			}			
			if (false !== $requestsInCurrentMinute) $requests += $requestsInCurrentMinute;
		}

		if (! $this->keysvisited[$key] ) {
			if (false === $requestsInCurrentMinute) {
				$this->memcache->set($key, 1, 0, $this->maxMinutes * 60 + 1);
			} else {
				$this->memcache->increment($key, 1);
			}
			$this->keysvisited[$key]=1;
		} else {
			$this->keysvisited[$key]++;
		}

		echo " You already have $requests requests in $minutes min<BR>";
		if ($requests > $allowedRequests) throw new RateExceededException;
	}

	private function getKeys($minutes) {
		$keys = array();
		$now = time();
		for ($time = $now - $minutes * 60; $time <= $now; $time += 60) {
			$keys[] = $this->prefix . date("dHi", $time);
		}

		return $keys;
	}
}
