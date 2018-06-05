<?php namespace Travoltron\Plaid;

use Carbon\Carbon;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Post\PostBodyInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

class Plaid
{
    /**
     * Initialize the Guzzle Client and make it ready for all requests
     */
    public static function client()
    {
        return new Guzzle(['base_uri' => config('plaid.baseUrl')]);
    }

    //////////
    // Auth //
    //////////

    /**
     * [addAuthUser description]
     * @method addAuthUser
     * @param  [type]      $username [description]
     * @param  [type]      $password [description]
     * @param  [type]      $pin      [description]
     * @param  [type]      $type     [description]
     */
    public static function addAuthUser($username, $password, $institution_id, $initial_products, $pin = null)
    {
        $json = [
                'json' => [
                    'credentials' => [
                            'username' => $username,
                            'password' => $password
                    ],
                    'institution_id' => $institution_id,
                    'initial_products' => $initial_products,                    
                    'public_key' => config('plaid.public_key'),
                    'options' => [
                        'webhook' => 'http://requestb.in/1hrnqlu1/'
                    ]
                ]
        ];

        if ($pin) {
            $json = collect($json['json'])->put('pin', $pin)->toArray();
        }
        
        try {
            $request = self::client()->post('link/item/create', $json);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [authMfa description]
     * @method authMfa
     * @param  [type]  $mfa         [description]
     * @param  [type]  $plaid_token [description]
     * @param  [type]  $method      [description]
     * @return [type]               [description]
     */
    public static function authMfa($mfa, $plaid_token, $method = null)
    {
        try {
            $request = self::client()->post('auth/step', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'mfa' => $mfa,
                    'access_token' => $plaid_token,
                    'options' => [
                        'send_method' => $method,
                        'login_only' => config('plaid.auth.login_only'),
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [getAuthData description]
     * @method getAuthData
     * @param  [type]      $plaid_token [description]
     * @return [type]                   [description]
     */
    public static function getAuthData($access_token)
    {
        try {
            $request = self::client()->post('auth/get', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $access_token,
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [updateAuthUser description]
     * @method updateAuthUser
     * @param  [type]         $username    [description]
     * @param  [type]         $password    [description]
     * @param  [type]         $pin         [description]
     * @param  [type]         $plaid_token [description]
     * @return [type]                      [description]
     */
    public static function updateAuthUser($username, $password, $pin = null, $plaid_token)
    {
        try {
            $request = self::client()->patch('auth', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'access_token' => $plaid_token
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [deleteAuthUser description]
     * @method deleteAuthUser
     * @param  [type]         $plaid_token [description]
     * @return [type]                      [description]
     */
    public static function deleteAuthUser($plaid_token)
    {
        try {
            $request = self::client()->delete('auth', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /////////////
    // Connect //
    /////////////

    /**
     * [addConnectUser description]
     * @method addConnectUser
     * @param  [type]         $username [description]
     * @param  [type]         $password [description]
     * @param  [type]         $pin      [description]
     * @param  [type]         $type     [description]
     * @param  string          $options  [description]
     */
    public static function addConnectUser($username, $password, $pin = null, $type, $webhook, $start_date = null, $end_date = null)
    {
        $start = ($start_date ? Carbon::parse($start_date)->toDateString() : Carbon::now()->subDays(config('plaid.connect.start_date'))->toDateString());
        $end = ($end_date ? Carbon::parse($end_date)->toDateString() : Carbon::now()->subDays(config('plaid.connect.end_date'))->toDateString());
        try {
            $request = self::client()->post('connect', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'type' => $type,
                    'options' => [
                        'login_only' => config('plaid.connect.login_only'),
                        'webhook' => $webhook,
                        'pending' => config('plaid.connect.pending'),
                        'list' => config('plaid.connect.list'),
                        'gte' => $start,
                        'lte' => $end
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [connectMfa description]
     * @method connectMfa
     * @param  [type]     $mfa         [description]
     * @param  [type]     $plaid_token [description]
     * @param  [type]     $method      [description]
     * @return [type]                  [description]
     */
    public static function connectMfa($mfa, $plaid_token, $method = null)
    {
        try {
            $request = self::client()->post('connect/step', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'mfa' => $mfa,
                    'access_token' => $plaid_token,
                    'options' => [
                        'send_method' => $method
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [updateConnectUser description]
     * @method updateConnectUser
     * @param  [type]            $username    [description]
     * @param  [type]            $password    [description]
     * @param  [type]            $pin         [description]
     * @param  [type]            $plaid_token [description]
     * @param  [type]            $webhook     [description]
     * @return [type]                         [description]
     */
    public static function updateConnectUser($username, $password, $pin = null, $plaid_token, $webhook = null)
    {
        try {
            $request = self::client()->patch('connect', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'access_token' => $plaid_token,
                    'options' => [
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    public static function getConnectData($plaid_token, $start_date = null, $end_date = null)
    {
        $start = ($start_date ? Carbon::parse($start_date)->toDateString() : Carbon::now()->subDays(config('plaid.connect.start_date'))->toDateString());
        $end = ($end_date ? Carbon::parse($end_date)->toDateString() : Carbon::now()->subDays(config('plaid.connect.end_date'))->toDateString());
        try {
            $request = self::client()->post('connect/get', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                    'options' => [
                        'pending' => config('plaid.connect.pending'),
                        'gte' => $start,
                        'lte' => $end
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [deleteConnectUser description]
     * @method deleteConnectUser
     * @param  [type]            $plaid_token [description]
     * @return [type]                         [description]
     */
    public static function deleteConnectUser($plaid_token)
    {
        try {
            $request = self::client()->delete('connect', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [getAccounts description]
     * @method getAccounts
     * @param  [type]      $plaid_token [description]
     * @return [type]                   [description]
     */
    public static function getConnectAccounts($plaid_token)
    {
        try {
            $request = self::client()->post('accounts/get', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token
                ]
            ]);
            return json_decode($request->getBody(), true)['accounts'];
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [getTransactions description]
     * @method getTransactions
     * @param  [type]          $plaid_token [description]
     * @return [type]                       [description]
     */
    public static function getConnectTransactions($plaid_token, $start_date = null, $end_date = null)
    {
        $start = ($start_date ? Carbon::parse($start_date)->toDateString() : Carbon::now()->subDays(config('plaid.connect.start_date'))->toDateString());
        $end = ($end_date ? Carbon::parse($end_date)->toDateString() : Carbon::now()->subDays(config('plaid.connect.end_date'))->toDateString());
        try {
            $request = self::client()->post('transactions/get', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                    'start_date' => $start,
                    'end_date' => $end,
                    'options' => [
                        'count' => 100,
                        'offset' => 0
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true)['transactions'];
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    //////////
    // Info //
    //////////

    /**
     * [addInfoUser description]
     * @method addInfoUser
     * @param  [type]      $username [description]
     * @param  [type]      $password [description]
     * @param  [type]      $pin      [description]
     * @param  [type]      $type     [description]
     * @param  [type]      $webhook  [description]
     */
    public static function addInfoUser($username, $password, $pin = null, $type, $webhook)
    {
        try {
            $request = self::client()->post('info', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'type' => $type,
                    'options' => [
                        'list' => config('plaid.connect.list'),
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [infoMfa description]
     * @method infoMfa
     * @param  [type]  $mfa         [description]
     * @param  [type]  $plaid_token [description]
     * @param  [type]  $method      [description]
     * @return [type]               [description]
     */
    public static function infoMfa($mfa, $plaid_token, $method = null)
    {
        try {
            $request = self::client()->post('info/step', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'mfa' => $mfa,
                    'access_token' => $plaid_token,
                    'options' => [
                        'send_method' => $method
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [updateInfoUser description]
     * @method updateInfoUser
     * @param  [type]         $username    [description]
     * @param  [type]         $password    [description]
     * @param  [type]         $pin         [description]
     * @param  [type]         $plaid_token [description]
     * @param  [type]         $webhook     [description]
     * @return [type]                      [description]
     */
    public static function updateInfoUser($username, $password, $pin = null, $plaid_token, $webhook = null)
    {
        try {
            $request = self::client()->patch('info', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'access_token' => $plaid_token,
                    'options' => [
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [deleteInfoUser description]
     * @method deleteInfoUser
     * @param  [type]         $plaid_token [description]
     * @return [type]                      [description]
     */
    public static function deleteInfoUser($plaid_token)
    {
        try {
            $request = self::client()->delete('info', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [getInfoData description]
     * @method getInfoData
     * @param  [type]      $plaid_token [description]
     * @return [type]                   [description]
     */
    public static function getInfoData($plaid_token)
    {
        try {
            $request = self::client()->post('identity/get', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    ////////////
    // Income //
    ////////////

    /**
     * [addIncomeUser description]
     * @method addIncomeUser
     * @param  [type]        $username [description]
     * @param  [type]        $password [description]
     * @param  [type]        $pin      [description]
     * @param  [type]        $type     [description]
     * @param  [type]        $webhook  [description]
     */
    public static function addIncomeUser($username, $password, $pin = null, $type, $webhook)
    {
        try {
            $request = self::client()->post('income', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'type' => $type,
                    'options' => [
                        'list' => config('plaid.income.list'),
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [incomeMfa description]
     * @method incomeMfa
     * @param  [type]    $mfa         [description]
     * @param  [type]    $plaid_token [description]
     * @param  [type]    $method      [description]
     * @return [type]                 [description]
     */
    public static function incomeMfa($mfa, $plaid_token, $method = null)
    {
        try {
            $request = self::client()->post('income/step', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'mfa' => $mfa,
                    'access_token' => $plaid_token,
                    'options' => [
                        'send_method' => $method
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [updateIncomeUser description]
     * @method updateIncomeUser
     * @param  [type]           $username    [description]
     * @param  [type]           $password    [description]
     * @param  [type]           $pin         [description]
     * @param  [type]           $plaid_token [description]
     * @param  [type]           $webhook     [description]
     * @return [type]                        [description]
     */
    public static function updateIncomeUser($username, $password, $pin = null, $plaid_token, $webhook = null)
    {
        try {
            $request = self::client()->patch('income', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'access_token' => $plaid_token,
                    'options' => [
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [deleteIncomeUser description]
     * @method deleteIncomeUser
     * @param  [type]           $plaid_token [description]
     * @return [type]                        [description]
     */
    public static function deleteIncomeUser($plaid_token)
    {
        try {
            $request = self::client()->delete('income', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [getIncomeData description]
     * @method getIncomeData
     * @param  [type]        $plaid_token [description]
     * @return [type]                     [description]
     */
    public static function getIncomeData($plaid_token)
    {
        try {
            $request = self::client()->post('income/get', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    //////////
    // Risk //
    //////////

    /**
     * [addRiskUser description]
     * @method addRiskUser
     * @param  [type]      $username [description]
     * @param  [type]      $password [description]
     * @param  [type]      $pin      [description]
     * @param  [type]      $type     [description]
     * @param  [type]      $webhook  [description]
     */
    public static function addRiskUser($username, $password, $pin = null, $type, $webhook)
    {
        try {
            $request = self::client()->post('risk', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'type' => $type,
                    'options' => [
                        'list' => config('plaid.risk.list'),
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [riskMfa description]
     * @method riskMfa
     * @param  [type]  $mfa         [description]
     * @param  [type]  $plaid_token [description]
     * @param  [type]  $method      [description]
     * @return [type]               [description]
     */
    public static function riskMfa($mfa, $plaid_token, $method = null)
    {
        try {
            $request = self::client()->post('risk/step', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'mfa' => $mfa,
                    'access_token' => $plaid_token,
                    'options' => [
                        'send_method' => $method
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [updateRiskUser description]
     * @method updateRiskUser
     * @param  [type]         $username    [description]
     * @param  [type]         $password    [description]
     * @param  [type]         $pin         [description]
     * @param  [type]         $plaid_token [description]
     * @param  [type]         $webhook     [description]
     * @return [type]                      [description]
     */
    public static function updateRiskUser($username, $password, $pin = null, $plaid_token, $webhook = null)
    {
        try {
            $request = self::client()->patch('risk', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'username' => $username,
                    'password' => $password,
                    'pin' => $pin,
                    'access_token' => $plaid_token,
                    'options' => [
                        'webhook' => $webhook
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [deleteRiskUser description]
     * @method deleteRiskUser
     * @param  [type]         $plaid_token [description]
     * @return [type]                      [description]
     */
    public static function deleteRiskUser($plaid_token)
    {
        try {
            $request = self::client()->delete('risk', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /**
     * [getRiskData description]
     * @method getRiskData
     * @param  [type]      $plaid_token [description]
     * @return [type]                   [description]
     */
    public static function getRiskData($plaid_token)
    {
        try {
            $request = self::client()->post('risk/get', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    /////////////
    // Balance //
    /////////////

    /**
     * [getBalances description]
     * @method getBalances
     * @param  [type]      $plaid_token [description]
     * @return [type]                   [description]
     */
    public static function getBalances($plaid_token, $account_ids = null)
    {
        try {
            $request = self::client()->post('accounts/balance/get', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $plaid_token,
                    // 'options' => [
                    //     'account_ids' => $account_ids
                    // ]
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    ///////////////////////////
    // Longtail Institutions //
    ///////////////////////////

    public static function longtail($count = 50, $offset = 1)
    {
        try {
            $request = self::client()->post('institutions/get', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'count' => $count,
                    'offset' => $offset
                ]
            ]);
            return json_decode($request->getBody(), true)['institutions'];
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    ////////////
    // Search //
    ////////////

    public static function search($query, $product = null, $institution_id = null)
    {
        try {
            $request = self::client()->post('institutions/search', [
                'json' => [
                    'query' => $query,
                    'products' => $product,
                    'public_key' => config('plaid.public_key'),
                    'options' => [
                        'include_display_data' => true
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true)['institutions'];
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    public static function getById($institution_id)
    {
        try {
            $request = self::client()->post('institutions/get_by_id', [
                'json' => [
                    'institution_id' => $institution_id,
                    'public_key' => config('plaid.public_key'),
                    'options' => [
                        'include_display_data' => true
                    ]
                ]
            ]);
            return json_decode($request->getBody(), true)['institution'];
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    public static function searchType($product)
    {
        try {
            $request = self::client()->post('institutions/all', [
                'json' => [
                    'products' => '["'.$product.'"]',
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'count' => 200
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    ////////////////
    // Categories //
    ////////////////

    /**
     * [$id description]
     * @var [type]
     */
    public static function categories($category_id = null)
    {
        $endpoint = ($category_id)?'/'.$category_id:'';
        try {
            $request = self::client()->get('categories/get'.$endpoint);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    //////////
    // Link //
    //////////

    /**
     * [exchangeToken description]
     * @method exchangeToken
     * @param [string]  $public_token Token returned from plaid link
     * @return [type] [description]
     */
    public static function exchangeToken($public_token)
    {
        $bodyArray = [
            'client_id' => config('plaid.client_id'),
            'secret' => config('plaid.secret'),
            'public_token' => $public_token
        ];
        try {
            $request = self::client()->post('item/public_token/exchange', [
                'json' => $bodyArray
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    public static function createPublicToken($access_token)
    {
        try {
            $request = self::client()->post('item/public_token/create', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $access_token,
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    public static function getItem($access_token)
    {
        try {
            $request = self::client()->post('item/get', [
                'json' => [
                    'client_id' => config('plaid.client_id'),
                    'secret' => config('plaid.secret'),
                    'access_token' => $access_token,
                ]
            ]);
            return json_decode($request->getBody(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }
}
