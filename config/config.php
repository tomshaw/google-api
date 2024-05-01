<?php

return [
    /**
     * Set the default token storage adapter to use.
     *
     * You can provide your own storage mechanism such as file or Redis by implementing the StorageAdapterInterface.
     */
    'token_storage_adapter' => TomShaw\GoogleApi\Storage\DatabaseStorageAdapter::class,

    /**
     * This structure should match the file downloaded from the "Download JSON" button on in the Google Developer Console.
     */
    'auth_config' => storage_path('app/google-api/client_secret.json'),

    /**
     * Set the application name, this is included in the User-Agent HTTP header.
     */
    'application_name' => 'Application Name',

    /**
     * Set the prompt hint. Valid values are none, consent and select_account.
     *
     * If no value is specified and the user has not previously authorized access, then the user is shown a consent screen.
     *
     * {@code "none"} Do not display any authentication or consent screens. Must not be specified with other values.
     * {@code "consent"} Prompt the user for consent. {@code "select_account"} Prompt the user to select an account.
     */
    'prompt' => 'consent',

    /**
     * Possible values for approval_prompt include: {@code "force"} to force the approval UI to appear.
     * {@code "auto"} to request auto-approval when possible. (This is the default value)
     */
    'approval_prompt' => 'auto',

    /**
     * Possible values for access_type include: {@code "offline"} to request offline access from the user.
     * {@code "online"} to request online access from the user.
     */
    'access_type' => 'offline',

    /**
     * If this is provided with the value true, and the authorization request is granted, the authorization
     * will include any previous authorizations granted to this user/application combination for other scopes.
     */
    'include_grant_scopes' => true,

    /**
     * Scopes to be requested as part of the OAuth2.0 flow.
     */
    'service_scopes' => [
        // Google\Service\Calendar::CALENDAR,
        // Google\Service\Gmail::GMAIL_SEND,
        // Google\Service\Books::BOOKS,
        // Google\Service\Drive::DRIVE,
    ],
];
