<?php
declare(strict_types=1);

namespace Symplify\MonorepoSplit;

// simple wrapper class to eliminate all functions in the entrypoint.

class GithubException extends \Exception{}
class Github {
    /*
     * Set the accesstoken and user to use for github api.
     */
    private static $user;
    private static $token;
    
    public static function useAuth(string $user, string $token): void
    {
        static::$user = $user;
        static::$token = $token;
    }
    public static function getRepo(string $userandrepo): ?object {
        return static::api(
            resource: "/repos/{$userandrepo}"
        );
    }
    /* 
     * Determine if the name is a organization
     */
    public static function getOrganization(string $org): ?object
    {
        return static::api(
            resource: "/orgs/{$org}"
        );
    }

    /* 
     * Determine if the name is a user
     */
    public static function getUser(string $user): ?object
    {
        return static::api(
            resource: "/users/{$user}"
        );
    }

    public static function createRepo(string $path, array $payload) 
    {
        return static::api(
            resource: $path,
            payload: $payload,
            method: 'POST'        
        );
    }
    /*
     * a basic wrapper to call the github API
     */
    private static function api(
        string $resource,
        string $method = 'GET',
        array $payload = []
    ): ?object 
    {

        if ( static::$token && static::$user) {
            $url = "https://" . static::$user .  ":" . static::$token . "@api.github.com$resource";
        } else {
            $url = "https://api.github.com$resource";
        }
        
        note(str_replace(self::$token,"***","APICALL: $method $url")); 

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, 'monorepo-split-github-action/1.0');
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 3);     
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        // post request
        if ( $method == "POST" ) {
            curl_setopt($ch, CURLOPT_POST, 1);
            if ( !empty($payload ) ) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }

        }

        $result = curl_exec($ch);
        curl_close($ch);
        
        $json = json_decode($result);
        if ( !$json ) {
            throw new GithubException($result);
        }

        if ( isset($json->message) ) {
            throw new GithubException($json->message. '[resource: '.$resource.', documentation: '.$json->documentation_url.']' );
        }
        return $json;
    }
}