<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class PaymentService
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function confirmPayment(string $paymentIntentId): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);
            
            return [
                'id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount / 100,
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception('Payment confirmation failed: ' . $e->getMessage());
        }
    }

    public function processRefund(string $paymentIntentId, float $amount): ?\Stripe\Refund
    {
        try {
            return $this->stripe->refunds->create([
                'payment_intent' => $paymentIntentId,
                'amount' => (int)($amount * 100),
            ]);
        } catch (ApiErrorException $e) {
            \Log::error('Refund failed: ' . $e->getMessage());
            return null;
        }
    }

    public function createPaymentIntent(float $amount, array $metadata = [], array $options = []): \Stripe\PaymentIntent
    {
        try {
            $params = array_merge([
                'amount' => (int)($amount * 100),
                'currency' => 'usd',
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ], $options);

            return $this->stripe->paymentIntents->create($params);
        } catch (ApiErrorException $e) {
            throw new \Exception('Payment intent creation failed: ' . $e->getMessage());
        }
    }

    public function capturePaymentIntent(string $paymentIntentId): \Stripe\PaymentIntent
    {
        try {
            return $this->stripe->paymentIntents->retrieve($paymentIntentId)->capture();
        } catch (ApiErrorException $e) {
            throw new \Exception('Payment capture failed: ' . $e->getMessage());
        }
    }

    public function cancelPaymentIntent(string $paymentIntentId): \Stripe\PaymentIntent
    {
        try {
            return $this->stripe->paymentIntents->cancel($paymentIntentId);
        } catch (ApiErrorException $e) {
            throw new \Exception('Payment cancellation failed: ' . $e->getMessage());
        }
    }

    public function createTransfer(?string $accountId, float $amount): ?\Stripe\Transfer
    {
        if (!$accountId) {
            return null;
        }

        try {
            return $this->stripe->transfers->create([
                'amount' => (int)($amount * 100),
                'currency' => 'usd',
                'destination' => $accountId,
            ]);
        } catch (ApiErrorException $e) {
            \Log::error('Transfer failed: ' . $e->getMessage());
            return null;
        }
    }
}

