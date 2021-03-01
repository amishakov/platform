<?php declare(strict_types=1);

namespace App\Application\Actions\Cup\Catalog;

class CatalogExportAction extends CatalogAction
{
    protected function createSpreadSheet()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $spreadsheet->getProperties()
            ->setCreator('WebSpace Engine CMS')
            ->setTitle('Export catalog data ' . date(\App\Domain\References\Date::DATETIME))
            ->setCategory('Export');

        $spreadsheet->setActiveSheetIndex(0)->setTitle('Export');

        return $spreadsheet;
    }

    protected function getCellCoordinate($index, $row = 1)
    {
        static $alphabet;

        if (!$alphabet) {
            $alphabet = range('A', 'Z');
        }

        return $alphabet[$index] . ($row + 1);
    }

    protected function action(): \Slim\Http\Response
    {
        // Fields
        $fields = trim($this->parameter('catalog_export_columns', ''));

        if ($fields) {
            $fields = array_map('trim', explode(PHP_EOL, $fields));
            $offset = [
                'rows' => max(0, +$this->parameter('catalog_import_export_offset_rows', 0)),
                'cols' => max(0, +$this->parameter('catalog_import_export_offset_cols', 0)),
            ];

            $categories = $this->catalogCategoryService->read([
                'status' => \App\Domain\Types\Catalog\CategoryStatusType::STATUS_WORK,
            ]);

            // Products
            switch (($category = $this->request->getParam('category', false))) {
                default:
                    if (!\Ramsey\Uuid\Uuid::isValid($category)) {
                        goto false;
                    }

                    $category = $categories->firstWhere('uuid', $category);
                    $products = $this->catalogProductService->read([
                        'category' => $category->getNested($categories)->pluck('uuid')->all(),
                        'status' => \App\Domain\Types\Catalog\ProductStatusType::STATUS_WORK,
                    ]);

                    break;

                false:
                case false:
                    $products = $this->catalogProductService->read([
                        'status' => \App\Domain\Types\Catalog\ProductStatusType::STATUS_WORK,
                    ]);

                    break;
            }

            $wizard = new \PhpOffice\PhpSpreadsheet\Helper\Html();
            $spreadsheet = $this->createSpreadSheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Write header row
            foreach ($fields as $index => $field) {
                $sheet
                    ->getCell($this->getCellCoordinate($index + $offset['cols'], 0 + $offset['rows']))
                    ->setValue($field)
                    ->getStyle()
                    ->getFont()
                    ->setBold(true);
            }

            $row = 0;
            $lastCategory = null;

            // Write table data row by row
            foreach ($products->sortBy('category') as $model) {
                /** @var \App\Domain\Entities\Catalog\Product $model */
                if ($lastCategory !== $model->getCategory()->toString()) {
                    // get header cell
                    $sheet
                        ->getCell($this->getCellCoordinate(0 + $offset['cols'], $row + 1 + $offset['rows']))
                        ->setValue($categories->firstWhere('uuid', $model->getCategory())->title ?? 'Без категории');

                    $lastCategory = $model->getCategory()->toString();
                    $row++;
                }

                // product attributes
                $attributes = $model->getAttributes();

                foreach ($fields as $index => $field) {
                    if (trim($field)) {
                        $cell = $sheet->getCell($this->getCellCoordinate($index + $offset['cols'], $row + 1 + $offset['rows']));

                        // set default vertical aligment
                        $cell
                            ->getStyle()
                            ->getAlignment()
                            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

                        switch ($field) {
                            case 'category':
                                $cell->setValue($categories->firstWhere('uuid', $model->getCategory())->title ?? 'Без категории');

                                break;

                            case 'description':
                            case 'extra':
                                $cell
                                    ->setValue(trim($wizard->toRichTextObject($model->$field)->getPlainText()));

                                break;

                            case 'priceFirst':
                            case 'price':
                            case 'priceWholesale':
                                $cell
                                    ->setValue($model->$field)
                                    ->getStyle()
                                    ->getNumberFormat()
                                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);

                                break;

                            case 'vendorcode':
                            case 'barcode':
                                $cell
                                    ->setValue($model->$field)
                                    ->getStyle()
                                    ->getAlignment()
                                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                                break;

                            case 'volume':
                            case 'stock':
                            case 'order':
                                $cell
                                    ->setValue($model->$field)
                                    ->getStyle()
                                    ->getNumberFormat()
                                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                                break;

                            case 'date':
                                $cell
                                    ->setValue(\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($model->getDate()))
                                    ->getStyle()
                                    ->getNumberFormat()
                                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME);

                                break;

                            default:
                                // other field
                                if (isset($model->$field)) {
                                    // simple field value
                                    $cell->setValue($model->$field);

                                } elseif (!$attributes->isEmpty()) {
                                    /** @var \App\Domain\Entities\Catalog\ProductAttribute $attribute */
                                    $attribute = $attributes->filter(fn($el) => $el->getAddress() === $field)->first();

                                    if ($attribute) {
                                        switch ($attribute->getType()) {
                                            case \App\Domain\Types\Catalog\AttributeTypeType::TYPE_STRING:
                                                $cell->setValue($attribute->getValue());

                                                break;

                                            case \App\Domain\Types\Catalog\AttributeTypeType::TYPE_INTEGER:
                                                $cell
                                                    ->setValue($attribute->getValue())
                                                    ->getStyle()
                                                    ->getNumberFormat()
                                                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                                                break;

                                            case \App\Domain\Types\Catalog\AttributeTypeType::TYPE_FLOAT:
                                                $cell
                                                    ->setValue($attribute->getValue())
                                                    ->getStyle()
                                                    ->getNumberFormat()
                                                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);

                                                break;
                                        }
                                    }
                                }

                                break;
                        }
                    }
                }

                $row++;
            }

            return $this->response
                ->withAddedHeader('Content-type', 'application/vnd.ms-excel')
                ->withAddedHeader('Content-Disposition', 'attachment; filename="export ' . date(\App\Domain\References\Date::DATETIME) . '.xls"')
                ->write(\PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls')->save('php://output'));
        }

        return $this->response->withAddedHeader('Location', $_SERVER['HTTP_REFERER'] ?? '/cup/catalog/product')->withStatus(301);
    }
}
