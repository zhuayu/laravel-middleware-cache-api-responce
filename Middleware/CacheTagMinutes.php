<?php

namespace App\Http\Middleware;

use Closure;
use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CacheTagMinutes
{

    protected $request;
    protected $next;
    protected $cacheKey;
    protected $cacheTag;
    protected $responseCache;
    protected $fromCache = 1;
    protected $minutes = 10;
    protected $refleshKey = 'reflesh';

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
        $this->clearCacheWithReflesh();
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
        $this->cacheKey = $this->getCacheKey();
        $this->cacheTag = $this->getCacheTag();
        $this->minutes = intval($minutes);
    }

    // if had Cache return or set Cache from callbackFunction
    protected function rememberResponseCache()
    {
        $this->responseCache = Cache::tags($this->cacheTag)->remember(
            $this->cacheKey,
            $this->minutes,
            function () {
                $this->fromCache = 0;
                $response = ($this->next)($this->request);
                \Log::info('new');
                return [
                    'content' => $response->getContent(),
                    'cacheExpireAt' => Carbon::now()->addMinutes($this->minutes)->format('Y-m-d H:i:s T'),
                ];
            }
        );
        return $this->responseCache;
    }

    protected function getCacheKey() {
        $url = $this->request->url();
        $queryParams = $this->request->query();
        if($this->hasReflesh()){
            unset($queryParams[$this->refleshKey]);
        }
        ksort($queryParams);
        $queryString = http_build_query($queryParams);
        $fullUrl = "{$url}?{$queryString}";
        return md5($fullUrl);
    }

    protected function getCacheTag() {
        return md5($this->request->path());
    }

    protected function addResponseHeader($response) {
        $headers = [
            'X-Cache' => $this->fromCache ? 'Hit from cache' : 'Missed',
            'X-Cache-Key' => $this->cacheKey,
            'X-Cache-Expires' => $this->responseCache['cacheExpireAt'],
        ];
        $response->headers->add($headers);
    }

    protected function clearCacheWithReflesh() {
        if($this->hasReflesh()) {
            $this->deleteWithTag();
        }
    }

    protected function deleteWithTag() {
        Cache::tags($this->cacheTag)->flush();
    }

    protected function hasReflesh() {
        $queryParams = $this->request->query();
        return isset($queryParams[$this->refleshKey]);
    }
}
