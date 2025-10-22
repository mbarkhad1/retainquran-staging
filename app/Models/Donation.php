<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
	use HasFactory;

	protected $fillable = [
		'user_id',
		'amount',
		'currency',
		'payment_type',
		'payment_frequency',
		'payment_provider_id',
		'subscription_id',
		'status',
		'description',
		'metadata',
		'payment_date',
		'next_payment_date',
	];

	protected $casts = [
		'amount' => 'decimal:2',
		'metadata' => 'array',
		'payment_date' => 'datetime',
		'next_payment_date' => 'datetime',
	];

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	/**
	 * Check if donation is recurring
	 */
	public function isRecurring(): bool
	{
		return $this->payment_frequency === 'monthly';
	}

	/**
	 * Check if donation is completed
	 */
	public function isCompleted(): bool
	{
		return $this->status === 'completed';
	}

	/**
	 * Check if donation is pending
	 */
	public function isPending(): bool
	{
		return $this->status === 'pending';
	}

	/**
	 * Check if donation is failed
	 */
	public function isFailed(): bool
	{
		return $this->status === 'failed';
	}

	/**
	 * Check if donation is cancelled
	 */
	public function isCancelled(): bool
	{
		return $this->status === 'cancelled';
	}

	/**
	 * Mark donation as completed
	 */
	public function markAsCompleted(): void
	{
		$this->update([
			'status' => 'completed',
			'payment_date' => now(),
		]);
	}

	/**
	 * Mark donation as failed
	 */
	public function markAsFailed(): void
	{
		$this->update(['status' => 'failed']);
	}

	/**
	 * Mark donation as cancelled
	 */
	public function markAsCancelled(): void
	{
		$this->update(['status' => 'cancelled']);
	}

	/**
	 * Update next payment date for recurring donations
	 */
	public function updateNextPaymentDate(): void
	{
		if ($this->isRecurring()) {
			$this->update([
				'next_payment_date' => now()->addMonth(),
			]);
		}
	}
}
