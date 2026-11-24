<?php

namespace Modules\Catalog\Listeners;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Mail;
use Modules\Catalog\Events\CollectionPublished;
use Modules\Catalog\Mail\CollectionPublishedMail;
use Modules\Catalog\Models\Collection;
use Modules\Customer\Contracts\Repositories\CustomerRepositoryInterface;

final class SendCollectionPublishedEmails implements ShouldQueueAfterCommit
{
    public int $tries = 1;

    public function __construct(private readonly CustomerRepositoryInterface $customers) {}

    public function handle(CollectionPublished $event): void
    {
        $collection = Collection::query()->find($event->collectionId);

        if ($collection === null || ! $collection->is_active) {
            return;
        }

        foreach ($this->customers->marketingOptedIn() as $customer) {
            Mail::to($customer->email)->queue(new CollectionPublishedMail($collection, $customer->name));
        }
    }
}
