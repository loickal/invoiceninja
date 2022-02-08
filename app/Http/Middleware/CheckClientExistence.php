<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\Middleware;

use App\Models\ClientContact;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckClientExistence
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $multiple_contacts = ClientContact::query()
            ->with('client.gateway_tokens','company')
            ->where('email', auth('contact')->user()->email)
            ->whereNotNull('email')
            ->where('email', '<>', '')
            // ->whereNull('deleted_at')
            ->distinct('company_id')
            ->distinct('email')
            ->whereNotNull('company_id')
            ->whereHas('client', function ($query) {
                return $query->where('is_deleted', false);
            })
            ->whereHas('company', function ($query){
                return $query->where('id', auth('contact')->user()->client->company_id);
            })
            ->get();

        /* This catches deleted clients who don't have access to the app. We automatically log them out here*/
        if (count($multiple_contacts) == 0) {
            Auth::logout();

            return redirect()->route('client.login')->with('message', 'Login disabled');
        }

        if (count($multiple_contacts) == 1 && !Auth::guard('contact')->check()) {
            Auth::guard('contact')->loginUsingId($multiple_contacts[0]->id, true);
        }

        session()->put('multiple_contacts', $multiple_contacts);

        return $next($request);
    }
}