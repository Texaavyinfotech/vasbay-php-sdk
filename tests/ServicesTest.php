<?php

namespace Texaavy\Vasbay\Tests;

/** every service method: HTTP method, endpoint and parameters as the v3 API expects them */
final class ServicesTest extends TestCase
{
    /* ---------------- SMS ---------------- */

    public function testSendWithToAliasAndDefaults(): void
    {
        $this->http->queueSuccess(['push_id' => 99, 'scheduled_at' => null, 'numbers' => []], [], 201);

        $r = $this->client()->sms()->send(['to' => '919876543210', 'message' => 'Test message']);

        $this->assertSame('POST', $this->http->last()['method']);
        $this->assertSame('/api/v3/sms/send', $this->http->lastPath());
        $this->assertSame(['message' => 'Test message', 'numbers' => '919876543210', 'route' => 2, 'sms_type' => 1], $this->http->lastJson());
        $this->assertSame(99, $r['push_id']);
        $this->assertSame(201, $r->status());
    }

    public function testSendAllOptionsCamelCase(): void
    {
        $this->http->queueSuccess([]);

        $this->client()->sms()->send([
            'numbers' => [9895000001, '9895000002'],
            'message' => 'Your OTP is 4821. -Texaavy',
            'senderId' => 'TXAAVY',
            'route' => 1,
            'smsType' => 3,
            'scheduleTime' => new \DateTimeImmutable('2026-12-25 10:30:45'),
            'removeDuplicate' => true,
            'templateId' => 12,
            'entityId' => '1701000000000000000',
            'contentId' => '1707000000000000000',
            'groupIds' => [4, 5],
        ]);

        $this->assertSame([
            'numbers' => ['9895000001', '9895000002'],
            'message' => 'Your OTP is 4821. -Texaavy',
            'sender_id' => 'TXAAVY',
            'route' => 1,
            'sms_type' => 3,
            'schedule_time' => '2026-12-25 10:30',
            'remove_duplicate' => 1,
            'template_id' => 12,
            'entity_id' => '1701000000000000000',
            'content_id' => '1707000000000000000',
            'group_ids' => [4, 5],
        ], $this->http->lastJson());
    }

    public function testSendAcceptsApiParameterNames(): void
    {
        $this->http->queueSuccess([]);

        $this->client()->sms()->send(['numbers' => '9895000001,9895000002', 'message' => 'x', 'sender_id' => 'TXAAVY', 'sms_type' => 2, 'route' => 4]);

        $this->assertSame(['numbers' => '9895000001,9895000002', 'message' => 'x', 'sender_id' => 'TXAAVY', 'sms_type' => 2, 'route' => 4], $this->http->lastJson());
    }

    public function testSendDropsEmptyValues(): void
    {
        $this->http->queueSuccess([]);

        $this->client()->sms()->send(['to' => '9895000001', 'message' => 'x', 'senderId' => '', 'templateId' => null, 'removeDuplicate' => false]);

        $json = $this->http->lastJson();
        $this->assertArrayNotHasKey('sender_id', $json);
        $this->assertArrayNotHasKey('template_id', $json);
        $this->assertSame(0, $json['remove_duplicate']);
    }

    public function testNumbersWinsOverToAlias(): void
    {
        $this->http->queueSuccess([]);

        $this->client()->sms()->send(['numbers' => '111111', 'to' => '222222', 'message' => 'x']);

        $this->assertSame('111111', $this->http->lastJson()['numbers']);
        $this->assertArrayNotHasKey('to', $this->http->lastJson());
    }

    public function testSendBulk(): void
    {
        $this->http->queueSuccess(['push_id' => 5]);
        $recipients = [['mobile' => '9895000001', 'ref' => 'INV-1', 'fields' => ['Dev', '1250']]];

        $this->client()->sms()->sendBulk(['message' => 'Dear #FIELD2#, due #FIELD3#', 'senderId' => 'TXAAVY', 'recipients' => $recipients, 'scheduleTime' => '2026-12-01 09:00']);

        $this->assertSame('/api/v3/sms/bulk', $this->http->lastPath());
        $this->assertSame([
            'message' => 'Dear #FIELD2#, due #FIELD3#', 'sender_id' => 'TXAAVY', 'recipients' => $recipients,
            'schedule_time' => '2026-12-01 09:00', 'route' => 2, 'sms_type' => 1,
        ], $this->http->lastJson());
    }

    public function testStatusByPushIdShortcut(): void
    {
        $this->http->queueSuccess([]);
        $this->client()->sms()->status(123);

        $this->assertSame('GET', $this->http->last()['method']);
        $this->assertSame('/api/v3/sms/status', $this->http->lastPath());
        $this->assertSame(['push_id' => '123'], $this->http->lastQuery());
    }

    public function testStatusQuery(): void
    {
        $this->http->queueSuccess([]);
        $this->client()->sms()->status(['numberId' => 7]);
        $this->assertSame(['number_id' => '7'], $this->http->lastQuery());

        $this->http->queueSuccess([]);
        $this->client()->sms()->status(['page' => 2]);
        $this->assertSame(['page' => '2'], $this->http->lastQuery());
    }

    public function testHistory(): void
    {
        $this->http->queueSuccess([]);
        $this->client()->sms()->history(['from' => '2026-10-01', 'to' => '2026-10-31', 'via' => 'api', 'scheduled' => true, 'page' => 3]);

        $this->assertSame('/api/v3/sms/history', $this->http->lastPath());
        $this->assertSame(['from' => '2026-10-01', 'to' => '2026-10-31', 'via' => 'api', 'scheduled' => '1', 'page' => '3'], $this->http->lastQuery());
    }

    public function testHistoryWithoutFiltersHasNoQueryString(): void
    {
        $this->http->queueSuccess([]);
        $this->client()->sms()->history();

        $this->assertSame('https://vasbay.com/api/v3/sms/history', $this->http->last()['url']);
    }

    public function testPushNumbers(): void
    {
        $this->http->queueSuccess([]);
        $this->client()->sms()->numbers(42, ['status' => 'failed', 'perPage' => 100]);

        $this->assertSame('/api/v3/sms/pushes/42/numbers', $this->http->lastPath());
        $this->assertSame(['status' => 'failed', 'per_page' => '100'], $this->http->lastQuery());
    }

    /* ---------------- account ---------------- */

    public function testAccount(): void
    {
        $account = $this->client()->account();
        $this->http->queueSuccess([])->queueSuccess([])->queueSuccess([]);

        $account->balance();
        $this->assertSame(['GET', '/api/v3/balance'], [$this->http->last()['method'], $this->http->lastPath()]);
        $account->me();
        $this->assertSame(['GET', '/api/v3/me'], [$this->http->last()['method'], $this->http->lastPath()]);
        $account->notifications();
        $this->assertSame(['GET', '/api/v3/notifications'], [$this->http->last()['method'], $this->http->lastPath()]);
    }

    /* ---------------- sender IDs ---------------- */

    public function testSenderIds(): void
    {
        $s = $this->client()->senderIds();
        $this->http->queueSuccess([])->queueSuccess([])->queueSuccess([])->queueSuccess(['id' => 3], [], 201);

        $s->list();
        $this->assertSame('https://vasbay.com/api/v3/sender-ids', $this->http->last()['url']);

        $s->list('approved', ['senderId' => 'TXAAVY', 'route' => 2]);
        $this->assertSame(['status' => 'approved', 'sender_id' => 'TXAAVY', 'route' => '2'], $this->http->lastQuery());

        $s->get(3);
        $this->assertSame('/api/v3/sender-ids/3', $this->http->lastPath());

        $s->request(['senderId' => 'TXAAVY', 'entityId' => '1701', 'companyName' => 'Texaavy', 'explanation' => 'TeXAAVY',
            'purpose' => 'OTP', 'sampleSms' => 'Your OTP is 1234. -Texaavy']);
        $this->assertSame('POST', $this->http->last()['method']);
        $this->assertSame('/api/v3/sender-ids', $this->http->lastPath());
        $this->assertSame(['sender_id' => 'TXAAVY', 'entity_id' => '1701', 'company_name' => 'Texaavy', 'explanation' => 'TeXAAVY',
            'purpose' => 'OTP', 'sample_sms' => 'Your OTP is 1234. -Texaavy', 'route' => 2], $this->http->lastJson());
    }

    /* ---------------- templates ---------------- */

    public function testTemplates(): void
    {
        $t = $this->client()->templates();
        $this->http->queueSuccess([])->queueSuccess([])->queueSuccess([]);

        $t->list(['status' => 'all', 'contentId' => '1707']);
        $this->assertSame('/api/v3/templates', $this->http->lastPath());
        $this->assertSame(['status' => 'all', 'content_id' => '1707'], $this->http->lastQuery());

        $t->get(8);
        $this->assertSame('/api/v3/templates/8', $this->http->lastPath());

        $t->submit(['contentId' => '1707', 'senderId' => 'TXAAVY', 'text' => 'Your OTP is {#var#}. -Texaavy']);
        $this->assertSame('POST', $this->http->last()['method']);
        $this->assertSame(['content_id' => '1707', 'sender_id' => 'TXAAVY', 'text' => 'Your OTP is {#var#}. -Texaavy', 'type' => 'transactional'], $this->http->lastJson());
    }

    /* ---------------- scheduled ---------------- */

    public function testScheduled(): void
    {
        $s = $this->client()->scheduled();
        $this->http->queueSuccess([])->queueSuccess([])->queueSuccess([])->queueSuccess([])->queueSuccess([]);

        $s->list(['status' => 'upcoming', 'type' => 'bulk']);
        $this->assertSame(['status' => 'upcoming', 'type' => 'bulk'], $this->http->lastQuery());

        foreach (['hold', 'resume', 'stop'] as $action) {
            $s->{$action}(77);
            $this->assertSame('POST', $this->http->last()['method']);
            $this->assertSame('/api/v3/scheduled/77/' . $action, $this->http->lastPath());
            $this->assertSame('{}', $this->http->last()['body']);
        }

        $s->stopDynamic(9);
        $this->assertSame('/api/v3/scheduled/dynamic/9/stop', $this->http->lastPath());
    }

    /* ---------------- groups ---------------- */

    public function testGroups(): void
    {
        $g = $this->client()->groups();
        $this->http->queueSuccess([])->queueSuccess([])->queueSuccess([]);

        $g->list(['q' => 'cust', 'page' => 2, 'perPage' => 50]);
        $this->assertSame('/api/v3/groups', $this->http->lastPath());
        $this->assertSame(['q' => 'cust', 'page' => '2', 'per_page' => '50'], $this->http->lastQuery());

        $g->get(4);
        $this->assertSame('/api/v3/groups/4', $this->http->lastPath());

        $g->contacts(4, ['q' => '9895']);
        $this->assertSame('/api/v3/groups/4/contacts', $this->http->lastPath());
        $this->assertSame(['q' => '9895'], $this->http->lastQuery());
    }

    /* ---------------- voice ---------------- */

    public function testVoiceReads(): void
    {
        $v = $this->client()->voice();
        $this->http->queueSuccess([])->queueSuccess([])->queueSuccess([])->queueSuccess([])->queueSuccess([])->queueSuccess([]);

        $v->balance();
        $this->assertSame('/api/v3/voice/balance', $this->http->lastPath());
        $v->files();
        $this->assertSame('/api/v3/voice/files', $this->http->lastPath());
        $v->callerIds();
        $this->assertSame('/api/v3/voice/caller-ids', $this->http->lastPath());
        $v->calls(['from' => '2026-10-01', 'perPage' => 10]);
        $this->assertSame('/api/v3/voice/calls', $this->http->lastPath());
        $this->assertSame(['from' => '2026-10-01', 'per_page' => '10'], $this->http->lastQuery());
        $v->call(15);
        $this->assertSame('https://vasbay.com/api/v3/voice/calls/15', $this->http->last()['url']);
        $v->call(15, 3);
        $this->assertSame(['page' => '3'], $this->http->lastQuery());
    }

    public function testVoiceSendAndCancel(): void
    {
        $v = $this->client()->voice();
        $this->http->queueSuccess(['id' => 15, 'state' => 'scheduled'], ['ignored_numbers' => []], 201)->queueSuccess([]);

        $r = $v->send(['fileId' => 3, 'to' => ['9895000001'], 'route' => 2, 'billingType' => 3, 'callerId' => '0471000000',
            'name' => 'Reminder', 'scheduleTime' => new \DateTime('2026-11-10 10:00')]);
        $this->assertSame('POST', $this->http->last()['method']);
        $this->assertSame('/api/v3/voice/calls', $this->http->lastPath());
        $this->assertSame(['file_id' => 3, 'route' => 2, 'billing_type' => 3, 'caller_id' => '0471000000', 'name' => 'Reminder',
            'schedule_time' => '2026-11-10 10:00', 'numbers' => ['9895000001']], $this->http->lastJson());
        $this->assertSame([], $r->meta('ignored_numbers'));

        $v->cancel(15);
        $this->assertSame('POST', $this->http->last()['method']);
        $this->assertSame('/api/v3/voice/calls/15/cancel', $this->http->lastPath());
    }
}
