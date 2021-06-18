<?php


namespace Last1971\ChipDipParser;


use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;

class ChipDipProductParser
{
    /**
     * @var Document
     */
    private Document $document;

    /**
     * ChipDipProductParser constructor.
     * @param string $html
     */
    public function __construct(string $html)
    {
        $this->document = new Document($html);
    }

    /**
     * @return array
     * @throws InvalidSelectorException
     */
    private function parseQuantities(): array
    {
        $lines = $this->document->find('.item__avail');
        return array_map(function ($line) {
            if (strpos($line->text(), 'запрос') || strpos($line->text(), 'доставка')) {
                return [
                    'quantity' => 0,
                    'unit' => 'шт.',
                    'reason' => 'Not availibale'
                ];
            }
            $valueUnit = explode(' ', $line->first('b')->text());
            return [
                'quantity' => $valueUnit[0],
                'unit' =>  $valueUnit[1],
                'reason' => str_replace($line->first('b')->text(), '', $line->text())
            ];
        }, $lines);
    }

    /**
     * @return int
     * @throws InvalidSelectorException
     */
    private function parseMultiple(): int
    {
        $multiple = $this->document->first('.ordering-price-w .font-mini .nw');
        return empty($multiple)
            ? 1
            : explode(' ', $multiple->text())[0];
    }

    /**
     * @param int $multiple
     * @return array[]
     * @throws InvalidSelectorException
     */
    private function parsePrices(int $multiple): array
    {
         $ret = [
            [
                'min' => $multiple,
                'price' => floatval(
                    preg_replace(
                        '/[^\d.,]/',
                        '',
                        $this->document->first('.ordering-price-w .ordering__value')->text())
                ),
                'valute' => 'RUB',
            ]
        ];
        foreach ($this->document->find('.ordering__discount') as $discount) {
            $price = floatval(
                preg_replace(
                    '/[^\d.,]/',
                    '',
                    $discount->first('.price')->text()
                )
            );
            if ($price > 0) {
                $ret[] = [
                    'min' => intval(explode(' ', $discount->first('b')->text())[0]),
                    'price' => $price,
                    'valute' => 'RUB',
                ];
            }
        }
        return $ret;
    }

    /**
     * @return array|string[]
     * @throws InvalidSelectorException
     */
    public function __invoke(): array
    {
        if (empty($this->document->find('.product_main-id span'))) {
            return [
                'error' => 'Not product with this code',
            ];
        }
        $multiple = $this->parseMultiple();
        return [
            'code' => $this->document->find('.product_main-id span')[1]->text(),
            'name' => $this->document->first('h1')->text(),
            'producer' => $this->document->first('[itemprop=brand]')->text(),
            'multiple' => $multiple,
            'quantities' => $this->parseQuantities(),
            'prices' => $this->parsePrices($multiple),
        ];
    }
}