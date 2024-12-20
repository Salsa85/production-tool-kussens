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

Route::post('/orders/{orderNumber}/labels', function (Request $request, $orderNumber) {
    // Validate the input
    $request->validate([
        'colli' => 'required|integer|min:1', // Ensure 'colli' is provided and is a positive integer
    ]);

   // dd($request->all());
    // Create the DHL API instance
    $api = new Api(
        'ee0e144f-5d23-400f-83bb-579977d4cb93',
        '4fcab3b2-e279-482c-8d9f-811990ed4117',
        'https://api-gw.dhlparcel.nl'
    );

    $trackAndTrace = null;
    $ids = [];

    // Fetch the order from the database
    $order = Order::where('id', $orderNumber)->first();

    if (!$order) {
        return response()->json(['message' => 'Order not found.'], 404);
    }

    try {
        // Generate labels and retrieve label IDs and the tracking number
        for ($i = 0; $i < $request->colli; $i++) {
            $label = $api->createLabel($order, $i, $request->colli);
            $label = json_decode($label->getBody()->getContents());

            $ids[] = $label->labelId;

            // Store the tracking number from the first label
            if ($i === 0) {
                $trackAndTrace = $label->trackerCode;
            }
        }

        // Retrieve the labels as a PDF
        $response = $api->getLabelPdf($ids);

        // Save the PDF to storage (optional)
        $fileName = sprintf('labels-%s.pdf', $order->id);
        $filePath = 'public/pdf/labels/' . $fileName;
        Storage::put($filePath, $response->getBody()->getContents());

        // Return the tracking number and label download URL
        $relativePath = 'pdf/labels/' . $fileName;
        $absolutePath = Storage::disk('public')->url($relativePath);

        $order->status = 'completed';
        $order->trackingcode = $trackAndTrace;
        $order->save();

        return response()->json([
            'message' => 'Labels retrieved successfully.',
            'tracking_number' => $trackAndTrace,
            'label_url' => $absolutePath,
        ]);
    } catch (\Exception $e) {
        return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
    }
});
