<?php

use PHPUnit\Framework\TestCase;

require_once INCLUDESPATH . 'easyparliament/glossary.php';

/**
 *
 */
class GlossaryTest extends TestCase {

    /**
     *
     */
    public function test_get_stopwords_returns_internal_stopwords_array() {
        $glossary = $this->newGlossaryWithoutConstructor();

        $expectedStopwords = ['the', 'of', 'to'];
        $this->setPrivateProperty($glossary, 'stopwords', $expectedStopwords);

        $this->assertSame($expectedStopwords, $glossary->get_stopwords());
    }

    /**
     *
     */
    public function test_get_alphabet_returns_internal_alphabet_array() {
        $glossary = $this->newGlossaryWithoutConstructor();

        $expectedAlphabet = [
            'A' => [1, 2],
            'B' => [3],
            'C' => [],
        ];
        $this->setPrivateProperty($glossary, 'alphabet', $expectedAlphabet);

        $this->assertSame($expectedAlphabet, $glossary->get_alphabet());
    }

    /**
     *
     */
    private function newGlossaryWithoutConstructor(): GLOSSARY {
        $reflection = new ReflectionClass(GLOSSARY::class);
        return $reflection->newInstanceWithoutConstructor();
    }

    /**
     *
     */
    private function setPrivateProperty(object $object, string $property, mixed $value): void {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }

}
