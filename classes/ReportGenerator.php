<?php

namespace Classes;

use Dompdf\Dompdf;
use Dompdf\Options;

class ReportGenerator
{
    private array $data = [];
    private string $template = '';
    private array $config = [];

    public function __construct()
    {
        $this->config = [
            'title' => 'Reporte',
            'orientation' => 'portrait',
            'format' => 'A4',
            'margin' => [10, 10, 10, 10]
        ];
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function setTemplate(string $template): self
    {
        $this->template = $template;
        return $this;
    }

    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    public function generatePDF(): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        // Procesar template
        $html = $this->processTemplate();

        $dompdf->loadHtml($html);
        $dompdf->setPaper($this->config['format'], $this->config['orientation']);
        $dompdf->render();

        return $dompdf->output();
    }

    public function generateExcel(): string
    {
        // Implementar generación de Excel usando PhpSpreadsheet
        // Por simplicidad, aquí devolvemos CSV
        return $this->generateCSV();
    }

    public function generateCSV(): string
    {
        if (empty($this->data)) {
            return '';
        }

        $output = fopen('php://temp', 'w+');

        // Headers
        fputcsv($output, array_keys($this->data[0]));

        // Data
        foreach ($this->data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    private function processTemplate(): string
    {
        if (empty($this->template)) {
            return $this->generateDefaultHTML();
        }

        $templatePath = __DIR__ . "/../views/reports/{$this->template}.php";

        if (!file_exists($templatePath)) {
            throw new \Exception("Template not found: {$this->template}");
        }

        ob_start();
        extract($this->data);
        include $templatePath;
        return ob_get_clean();
    }

    private function generateDefaultHTML(): string
    {
        $html = "<!DOCTYPE html><html><head>";
        $html .= "<meta charset='UTF-8'>";
        $html .= "<title>{$this->config['title']}</title>";
        $html .= "<style>
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>";
        $html .= "</head><body>";

        $html .= "<h1>{$this->config['title']}</h1>";

        if (!empty($this->data)) {
            $html .= "<table>";
            $html .= "<tr>";
            foreach (array_keys($this->data[0]) as $header) {
                $html .= "<th>" . ucfirst($header) . "</th>";
            }
            $html .= "</tr>";

            foreach ($this->data as $row) {
                $html .= "<tr>";
                foreach ($row as $cell) {
                    $html .= "<td>" . htmlspecialchars($cell) . "</td>";
                }
                $html .= "</tr>";
            }
            $html .= "</table>";
        }

        $html .= "</body></html>";

        return $html;
    }
}
