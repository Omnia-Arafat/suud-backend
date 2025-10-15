<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/health",
     *     summary="Health check endpoint",
     *     description="Returns the health status of the SU'UD platform API",
     *     tags={"System"},
     *     @OA\Response(
     *         response=200,
     *         description="System is healthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="OK"),
     *             @OA\Property(property="timestamp", type="string", format="datetime", example="2025-01-01T12:00:00Z"),
     *             @OA\Property(property="database", type="string", example="connected")
     *         )
     *     )
     * )
     */
    public function health()
    {
        try {
            // Test database connection
            DB::connection()->getPdo();
            $databaseStatus = 'connected';
        } catch (\Exception $e) {
            $databaseStatus = 'disconnected';
        }

        return response()->json([
            'status' => 'OK',
            'timestamp' => now()->toISOString(),
            'database' => $databaseStatus
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/public/info",
     *     summary="Get public system information",
     *     description="Returns basic information about the SU'UD platform for public display",
     *     tags={"System"},
     *     @OA\Response(
     *         response=200,
     *         description="System information retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="SU'UD Platform"),
     *             @OA\Property(property="description", type="string", example="Job Portal Platform for Saudi Arabia"),
     *             @OA\Property(property="version", type="string", example="1.0.0"),
     *             @OA\Property(property="contact_email", type="string", format="email", example="RSL111@hotmail.com")
     *         )
     *     )
     * )
     */
    public function info()
    {
        return response()->json([
            'name' => 'SU\'UD Platform',
            'description' => 'Job Portal Platform for Saudi Arabia',
            'version' => '1.0.0',
            'contact_email' => 'RSL111@hotmail.com'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/contact",
     *     summary="Submit contact form",
     *     description="Submit a contact form message to the SU'UD platform administrators",
     *     tags={"System"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Contact form data",
     *         @OA\JsonContent(
     *             required={"name", "email", "subject", "message"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, example="john.doe@example.com"),
     *             @OA\Property(property="subject", type="string", maxLength=255, example="Inquiry about job posting"),
     *             @OA\Property(property="message", type="string", maxLength=5000, example="I have a question about...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact form submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Thank you for your message. We will get back to you soon!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="subject", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="message", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="There was an error sending your message. Please try again later.")
     *         )
     *     )
     * )
     */
    public function contact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000'
        ]);

        try {
            // Log the contact form submission
            \Log::info('Contact form submission', [
                'name' => $request->name,
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()
            ]);

            // Send email notification to admin
            $adminEmail = 'RSL111@hotmail.com';
            $emailData = [
                'name' => $request->name,
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
                'ip' => $request->ip(),
                'timestamp' => now()->format('Y-m-d H:i:s')
            ];

            // Send email using Laravel Mail
            \Mail::send([], [], function ($mail) use ($emailData, $adminEmail) {
                $mail->to($adminEmail)
                    ->subject('New Contact Form Submission - ' . $emailData['subject'])
                    ->html(
                        '<h2>New Contact Form Submission</h2>' .
                        '<p><strong>Name:</strong> ' . htmlspecialchars($emailData['name']) . '</p>' .
                        '<p><strong>Email:</strong> ' . htmlspecialchars($emailData['email']) . '</p>' .
                        '<p><strong>Subject:</strong> ' . htmlspecialchars($emailData['subject']) . '</p>' .
                        '<p><strong>Message:</strong></p>' .
                        '<div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0;">' .
                        nl2br(htmlspecialchars($emailData['message'])) .
                        '</div>' .
                        '<hr>' .
                        '<p><small><strong>Submitted:</strong> ' . $emailData['timestamp'] . '</small></p>' .
                        '<p><small><strong>IP Address:</strong> ' . htmlspecialchars($emailData['ip']) . '</small></p>'
                    );
            });

            // Send auto-reply to user
            \Mail::send([], [], function ($mail) use ($emailData) {
                $mail->to($emailData['email'])
                    ->subject('Thank you for contacting SU\'UD Platform')
                    ->html(
                        '<h2>Thank you for contacting us!</h2>' .
                        '<p>Dear ' . htmlspecialchars($emailData['name']) . ',</p>' .
                        '<p>We have received your message and will get back to you within 24-48 hours.</p>' .
                        '<p><strong>Your message:</strong></p>' .
                        '<div style="background-color: #f0f8ff; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #4f46e5;">' .
                        '<strong>Subject:</strong> ' . htmlspecialchars($emailData['subject']) . '<br><br>' .
                        nl2br(htmlspecialchars($emailData['message'])) .
                        '</div>' .
                        '<p>Best regards,<br>The SU\'UD Team</p>' .
                        '<hr>' .
                        '<p><small>This is an automated message. Please do not reply to this email.</small></p>'
                    );
            });

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your message. We will get back to you soon!'
            ]);
        } catch (\Exception $e) {
            \Log::error('Contact form error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'There was an error sending your message. Please try again later.'
            ], 500);
        }
    }
}