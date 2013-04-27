php-ratelimiter
===============

A small class that uses Memcache to allow only a certain number of requests per a certain amount of minutes.

The class works around the problem that the timeframe is constantly moving, i.e. every new minute the timeframe is different. See my [blogpost](http://alexander.kirk.at/2013/04/19/add-a-rate-limit-to-your-website/).

The code is released under an MIT license.

Usage
-----

```php
$rateLimiter = new RateLimiter(new Memcache(), $_SERVER["REMOTE_ADDR"]);
try {
	// allow a maximum of 100 requests for the IP in 5 minutes
	$rateLimiter->limitRequestsInMinutes(100, 5);
} catch (RateExceededException $e) {
	header("HTTP/1.0 529 Too Many Requests");
	exit;
}
```

Remarks
-------

The script creates a memcached entry per IP and minute.

If you want to protect multiple resources with different limits, use the third parameter of the constructor to namespace it:

```php
// script1.php
$rateLimiter = new RateLimiter(new Memcache(), $_SERVER["REMOTE_ADDR"], "script1");
try { ... }
// script2.php
$rateLimiter = new RateLimiter(new Memcache(), $_SERVER["REMOTE_ADDR"], "script2");
try { ... }
```

You can also use something else as a second parameter, for example a `session_id` to limit the requests per user instead of IP address.
