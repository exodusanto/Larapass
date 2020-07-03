<?php

namespace DarkGhostHunter\Larapass\Http;

use Illuminate\Http\Request;
use DarkGhostHunter\Larapass\Facades\WebAuthn;
use DarkGhostHunter\Larapass\Events\AttestationSuccessful;
use DarkGhostHunter\Larapass\Contracts\WebAuthnAuthenticatable;

trait AttestsWebAuthn
{
    /**
     * Returns a challenge to be verified by the user device.
     *
     * @param  \DarkGhostHunter\Larapass\Contracts\WebAuthnAuthenticatable  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function options(WebAuthnAuthenticatable $user)
    {
        return response()->json(WebAuthn::generateAttestation($user));
    }

    /**
     * Registers a device for further WebAuthn authentication.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \DarkGhostHunter\Larapass\Contracts\WebAuthnAuthenticatable  $user
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request, WebAuthnAuthenticatable $user)
    {
        // We'll validate the challenge coming from the authenticator and instantly
        // save it into the credentials store. If the data is invalid we will bail
        // out and return a non-authorized response since we can't save the data.
        $validCredential = WebAuthn::validateAttestation(
            $request->validate($this->attestationRules()), $user
        );

        if ($validCredential) {
            $user->addCredential($validCredential);

            event(new AttestationSuccessful($user, $validCredential));

            return $this->credentialRegistered($user, $validCredential) ?? response()->noContent();
        }

        return response()->noContent(422);
    }

    /**
     * The attestation rules to validate the incoming JSON payload.
     *
     * @return array|string[]
     */
    protected function attestationRules()
    {
        return [
            'id'                         => 'required|string',
            'rawId'                      => 'required|string',
            'response.attestationObject' => 'required|string',
            'response.clientDataJSON'    => 'required|string',
            'type'                       => 'required|string',
        ];
    }

    /**
     * The user has registered a credential.
     *
     * @param  \DarkGhostHunter\Larapass\Contracts\WebAuthnAuthenticatable  $user
     * @param  \Webauthn\PublicKeyCredentialSource  $credentials
     * @return void|mixed
     */
    protected function credentialRegistered($user, $credentials)
    {
        // ...
    }
}