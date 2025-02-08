<?php

use App\Helpers\Api;
use App\Http\Controllers\OrderController;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. They are protected
| by your tool's "Authorize" middleware by default. Now, go build!
|
*/

 Route::get('/orders/{orderNumber}', function ($orderNumber) {

     $order = Order::where('id', $orderNumber)->first();

     if (!$order) {
         return response()->json([
             'message' => 'Order not found.',
         ], 404);
     }

     return response()->json([
         'order' => $order,
     ]);
 });

Route::get('/orders/{orderNumber}/pdf', function ($orderNumber) {
    $order = Order::where('id', $orderNumber)->first();

    if (!$order) {
        return response()->json(['message' => 'Order not found.'], 404);
    }

    // Pass data to a Blade view to generate the PDF
    $pdf = Pdf::loadView('pdf.package', ['models' => [$order]]);

    // Return the PDF inline
    return $pdf->stream("order-{$orderNumber}.pdf");
});

Route::get('/orders/{orderNumber}/labels', function (Request $request, $orderNumber) {
    // Validate the input
    $request->validate([
        'colli' => 'required|integer|min:1',
    ]);

    $api = new Api(
        config('production.api.code'),
        config('production.api.user_key'),
        config('production.api.base_url')
    );

    $order = Order::where('id', $orderNumber)->first();

    if (!$order) {
        return response()->json(['message' => 'Order not found.'], 404);
    }

    try {
        $ids = [];
        $trackAndTrace = null;

        // Generate labels
        for ($i = 0; $i < $request->colli; $i++) {
            $label = $api->createLabel($order, $i, $request->colli);
            $label = json_decode($label->getBody()->getContents());
            $ids[] = $label->labelId;

            if ($i === 0) {
                $trackAndTrace = $label->trackerCode;
            }
        }

        // Fetch the labels as a PDF
        $response = $api->getLabelPdf($ids);

        // Stream the PDF directly
        $pdfContent = $response->getBody()->getContents();
        $fileName = sprintf('labels-%s.pdf', $order->id);

        // set status to completed
        $order->status = 'completed';
        // save track and trace to order
        $order->trackingcode = $trackAndTrace;
        $order->save();

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename={$fileName}");

    } catch (\Exception $e) {
        return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
    }
});
