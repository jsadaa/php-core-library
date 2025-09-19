<?php

declare(strict_types = 1);

namespace Jsadaa\PhpCoreLibrary\Tests\Str\Functional;

use Jsadaa\PhpCoreLibrary\Modules\Collections\Sequence\Sequence;
use Jsadaa\PhpCoreLibrary\Primitives\Str\Str;
use PHPUnit\Framework\TestCase;

final class StrFunctionalTest extends TestCase
{
    public function testTextProcessingWorkflow(): void
    {
        $text = Str::from("  The Quick Brown Fox  \n  Jumps Over\t\tThe Lazy Dog  ");

        $processed = $text
            ->trim()
            ->toLowercase()
            ->replace(Str::from(' '), Str::from('-'));

        $this->assertStringContainsString('the-quick-brown-fox', $processed->toString());
        $this->assertStringContainsString('jumps-over', $processed->toString());
        $this->assertStringContainsString('the-lazy-dog', $processed->toString());

        $this->assertStringStartsNotWith(' ', $processed->toString());
        $this->assertStringEndsNotWith(' ', $processed->toString());

        $firstPart = $processed->take(15);
        $this->assertSame('the-quick-brown', $firstPart->toString());

        $lastPart = $processed->skip($processed->len()->toInt() - 8);
        $this->assertSame('lazy-dog', $lastPart->toString());
    }

    public function testCsvParsing(): void
    {
        $csvData = Str::from("name,age,city\njohn,25,new york\nsarah,32,los angeles\nmike,41,chicago");

        $lines = $csvData->split("\n");
        $this->assertSame(4, $lines->len()->toInt());

        // Parse header
        $header = $lines->get(0)->unwrapOr(null);
        $headerFields = $header->split(',');
        $this->assertSame(3, $headerFields->len()->toInt());

        // Parse data rows
        $dataRows = Sequence::new();

        for ($i = 1; $i < $lines->len()->toInt(); $i++) {
            $row = $lines->get($i)->unwrapOr(null);
            $fields = $row->split(',');

            $rowData = [];

            for ($j = 0; $j < $headerFields->len()->toInt(); $j++) {
                $fieldName = $headerFields->get($j)->unwrapOr(null)->toString();
                $fieldValue = $fields->get($j)->unwrapOr(null)->toString();
                $rowData[$fieldName] = $fieldValue;
            }

            $dataRows = $dataRows->push($rowData);
        }

        $this->assertSame(3, $dataRows->len()->toInt());
        $firstRow = $dataRows->get(0)->unwrap();
        $this->assertSame('john', $firstRow['name']);
        $this->assertSame('25', $firstRow['age']);
        $this->assertSame('new york', $firstRow['city']);
    }

    public function testMultilingualTextProcessing(): void
    {
        $text = Str::from(
            "English: Hello World\n" .
            "French: Bonjour le monde\n" .
            "German: Hallo Welt\n" .
            "Spanish: Hola Mundo\n" .
            "Chinese: 你好世界\n" .
            "Arabic: مرحبا بالعالم\n" .
            "Russian: Привет мир\n" .
            "Japanese: こんにちは世界\n",
        );

        $matches = $text->matches('/Hola|Hello|Bonjour|Hallo|你好|مرحبا|Привет|こんにちは/u');

        $this->assertSame(8, $matches->len()->toInt());

        $replaced = $text->replace(Str::from('English: '), Str::from('Greeting: '))
            ->replace(Str::from('French: '), Str::from('Greeting: '))
            ->replace(Str::from('German: '), Str::from('Greeting: '))
            ->replace(Str::from('Spanish: '), Str::from('Greeting: '))
            ->replace(Str::from('Chinese: '), Str::from('Greeting: '))
            ->replace(Str::from('Arabic: '), Str::from('Greeting: '))
            ->replace(Str::from('Russian: '), Str::from('Greeting: '))
            ->replace(Str::from('Japanese: '), Str::from('Greeting: '));

        $greetingMatches = $replaced->matches('/Greeting:/u');
        $this->assertSame(8, $greetingMatches->len()->toInt());
    }

    public function testUrlBuilding(): void
    {
        $scheme = Str::from('https');
        $domain = Str::from('example.com');
        $path = Str::from('/api/v1/users');

        $queryParams = [
            'id' => '12345',
            'format' => 'json',
            'fields' => 'name,email,address',
        ];

        $queryString = Str::new();
        $first = true;

        foreach ($queryParams as $key => $value) {
            $prefix = $first ? '?' : '&';
            $queryString = $queryString->append(Str::from($prefix . $key . '=' . $value));
            $first = false;
        }

        $url = Str::new()
            ->append(Str::from($scheme->toString() . '://'))
            ->append(Str::from($domain->toString()))
            ->append(Str::from($path->toString()))
            ->append(Str::from($queryString->toString()));

        $expectedUrl = 'https://example.com/api/v1/users?id=12345&format=json&fields=name,email,address';
        $this->assertSame($expectedUrl, $url->toString());

        $this->assertTrue($url->startsWith('https://'));
        $this->assertTrue($url->contains('example.com'));

        $protocol = $url->take(8);
        $this->assertSame('https://', $protocol->toString());

        $domain = $url->skip(8)->take(10);
        $this->assertSame('example.co', $domain->toString());

        $path = $url->skip(19)->take(11);
        $this->assertSame('/api/v1/use', $path->toString());
    }

    public function testTextExtractionAndAnalysis(): void
    {
        $jsonText = Str::from('{"users":[{"name":"John","email":"john@example.com"},'
            . '{"name":"Alice","email":"alice@example.com"},'
            . '{"name":"Bob","email":"bob@example.com"}]}');

        // Extract email addresses using regex
        $emailMatches = $jsonText->matches('/(\w+@\w+\.\w+)/u');
        $this->assertSame(3, $emailMatches->len()->toInt());

        // Extract patterns with their positions
        $nameIndices = $jsonText->matchIndices('/"name":"(\w+)"/u');
        $this->assertSame(3, $nameIndices->len()->toInt());

        $this->assertTrue($jsonText->contains('name'));
        $this->assertTrue($jsonText->contains('email'));

        $userPart = $jsonText->skip(2)->take(5);
        $this->assertSame('users', $userPart->toString());

        $namePart = $jsonText->skip(12)->take(4);
        $this->assertSame('name', $namePart->toString());

        $words = $jsonText->removeMatches('/[{}\[\]",:]/u')
            ->toString();

        $this->assertStringContainsString('users', $words);
        $this->assertStringContainsString('name', $words);
        $this->assertStringContainsString('email', $words);
        $this->assertStringContainsString('John', $words);
    }

    public function testTemplateProcessing(): void
    {
        $template = Str::from("Dear {{name}},\n\nThank you for your purchase of {{product}} on {{date}}."
            . "\n\nYour order number is {{order_id}}.\n\nRegards,\nThe {{company}} Team");

        $processed = $template
            ->replace(Str::from('{{name}}'), Str::from('John Smith'))
            ->replace(Str::from('{{product}}'), Str::from('Premium Widget'))
            ->replace(Str::from('{{date}}'), Str::from('2023-04-15'))
            ->replace(Str::from('{{order_id}}'), Str::from('ORD-12345'))
            ->replace(Str::from('{{company}}'), Str::from('Acme Corp'));

        $this->assertFalse($processed->contains('{{'));
        $this->assertFalse($processed->contains('}}'));

        $this->assertTrue($processed->contains('Dear John Smith'));
        $this->assertTrue($processed->contains('Premium Widget'));
        $this->assertTrue($processed->contains('ORD-12345'));

        $firstLine = $processed->split("\n")->get(0)->unwrap();
        $greeting = $firstLine->take(4);
        $name = $firstLine->skip(5);

        $this->assertSame('Dear', $greeting->toString());
        $this->assertSame('John Smith,', $name->toString());
    }
}
