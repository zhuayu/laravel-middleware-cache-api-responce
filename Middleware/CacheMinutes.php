<?php

namespace App\Http\Middleware;

use Closure;
use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CacheMinutes
{

    protected $request;
    protected $next;
    protected $cacheKey;
    protected $responseCache;
    protected $fromCache = 1;
    protected $minutes = 10;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $minutes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $minutes = 10)
    {
        $this->defaultVarible($request, $next, $minutes);
        $this->rememberResponseCache();
        $datas = json_decode($this->responseCache['content'], true);
        $response = response($datas);
        $this->addResponseHeader($response);
        return $response;
    }

    protected function defaultVarible($request, $next, $minutes)
    {
        $this->request = $request;
        $this->next = $next;
        $this->cacheKey = md5($request->fullUrl());
        $this->minutes = intval($minutes);
    }

    // if had Cache return or set Cache from callbackFunction
    protected function rememberResponseCache()
    {
        $this->responseCache = Cache::remember(
            $this->cacheKey,
            $this->minutes,
            function () {
                $this->fromCache = 0;
                $response = ($this->next)($this->request);
                return [
                    'content' => $response->getContent(),
                    'cacheExpireAt' => Carbon::now()->addMinutes($this->minutes)->format('Y-m-d H:i:s T'),
                ];
            }
        );
        return $this->responseCache;
    }

    protected function addResponseHeader($response) {
        $headers = [
            'X-Cache' => $this->fromCache ? 'Hit from cache' : 'Missed',
            'X-Cache-Key' => $this->cacheKey,
            'X-Cache-Expires' => $this->responseCache['cacheExpireAt'],
        ];
        $response->headers->add($headers);
    }
}
