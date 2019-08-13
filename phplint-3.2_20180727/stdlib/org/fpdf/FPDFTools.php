<?php
namespace org\fpdf;

require_once __DIR__ . "/../../all.php";
use it\icosaedro\utils\UString;


class FPDFTools {
	
	/**
	 * Draw a grid with scale in the current page using current measurement
	 * unit and a suitable grid spacing. The grid has its origin in the top
	 * left corner of the page.
	 * @param FPDF $pdf
	 * @return void
	 */
	public static function drawGrid($pdf)
	{
		// guess a suitable grid step
		$step_min = 20.0/$pdf->k; // 20 pt grid spacing min
		$step = 1;
		while($step < $step_min){
			// $step too small
			// try with $step = 1, 2, 5, 10, 20, 50, 100, ...
			$step *= 2;
			$s = (string) $step;
			if( $s[0] === "4" )
				$step = (int)($step / 4) * 5;
		} while($step < $step_min);

		// save context
		$ori_x = $pdf->getX();
		$ori_y = $pdf->getY();
		$ori_font = $pdf->currentFont;
		$ori_fontSizePt = $pdf->fontSizePt;

		$ori_bMargin = $pdf->bMargin;
		$ori_autoPageBreak = $pdf->setAutoPageBreak(FALSE);
		$pdf->setDrawColor(200, 200, 200);
		$font = new FontCore(FontCore::COURIER);
		$pdf->setFont($font, '', 9);
		$pdf->setTextColor(200, 100, 100);

		// draw horizontal lines
		$x1 = 0.0;
		$x2 = $pdf->w;
		for($y1 = 0.0; $y1 < $pdf->h; $y1 += $step){
			$y2 = $y1;
			$pdf->line($x1, $y1, $x2, $y2);
			$pdf->text($x1 + 0.5 * $pdf->w, $y1, UString::fromASCII((string)$y1));
		}

		// draw vertical lines
		$y1 = 0.0;
		$y2 = $pdf->h;
		for($x1 = 0.0; $x1 < $pdf->w; $x1 += $step){
			$x2 = $x1;
			$pdf->line($x1, $y1, $x2, $y2);
			$pdf->text($x1, $y1 + 0.5 * $pdf->h, UString::fromASCII((string)$x1));
		}

		// restore context
		$pdf->setDrawColor(0, 0, 0);
		$pdf->setTextColor(0, 0, 0);
		$pdf->setXY($ori_x, $ori_y);
		$pdf->setAutoPageBreak($ori_autoPageBreak, $ori_bMargin);
		$pdf->setFont($ori_font, '', $ori_fontSizePt);
	}
	
}
