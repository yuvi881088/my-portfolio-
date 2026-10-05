<?php
/**
 * AI Chatbot API Endpoint (PHP)
 * Victorious Innovatech Solutions
 * 
 * Method: POST
 * Accepts: JSON { "messages": [ { "role": "user", "content": "..." } ] } 
 *          OR { "message": "user inquiry" }
 * Returns: JSON { "message": { "role": "assistant", "content": "..." } }
 */

// Error handling settings
error_reporting(E_ALL);
ini_set('display_errors', 0);

// CORS Headers - Allow cross-origin requests from frontend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => ['role' => 'assistant', 'content' => 'Method Not Allowed. Only POST requests are supported.']
    ]);
    exit;
}

// Include configuration
require_once __DIR__ . '/config.php';

// Read input data
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

// Extract latest user message
$userMessage = '';
if (isset($data['messages']) && is_array($data['messages'])) {
    // Traverse backwards to find the latest user message
    for ($i = count($data['messages']) - 1; $i >= 0; $i--) {
        if (isset($data['messages'][$i]['role']) && $data['messages'][$i]['role'] === 'user') {
            $userMessage = trim($data['messages'][$i]['content'] ?? '');
            break;
        }
    }
} elseif (isset($data['message'])) {
    $userMessage = trim($data['message']);
}

if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => [
            'role' => 'assistant',
            'content' => 'Please provide a message so I can assist you.'
        ]
    ]);
    exit;
}

/**
 * Intelligent Built-in Knowledge Base Engine for Victorious Innovatech
 */
function generateLocalAIResponse($userQuery) {
    $q = mb_strtolower(trim($userQuery));
    $companyName = COMPANY_NAME;
    $phone = COMPANY_PHONE_FORMATTED;
    $whatsapp = COMPANY_WHATSAPP_URL;
    $email = COMPANY_EMAIL;
    $address = COMPANY_ADDRESS;

    // 1. Greetings
    if (preg_match('/\b(hi|hello|hey|namaste|hlo|hola|good morning|good afternoon|good evening)\b/iu', $q)) {
        return "Hello! 👋 Welcome to **{$companyName}**.\n\nI am your AI Assistant. How can I help you today? You can ask me about:\n- 💻 Our Web & Mobile App Services\n- 📱 Recent Portfolio & Case Studies\n- 💰 Project Cost & Timelines\n- 📞 How to contact our team in Indore";
    }

    // 2. Pricing & Cost
    if (preg_match('/(price|cost|budget|charge|rate|fees|kitna|paisa|kharcha|charges|quotation|quote)/iu', $q)) {
        return "💰 **Pricing & Estimates at {$companyName}:**\n\nOur project costs depend on features, platform (Web, iOS, Android), and scope:\n- **Standard Websites:** Typically 2-4 weeks turnaround.\n- **Custom Mobile & Web Apps:** Detailed custom quotes based on your exact specifications.\n\nWe provide **free technical consultations and project estimates**! You can message us directly on WhatsApp at [{$phone}]({$whatsapp}) or submit your requirements on our contact form.";
    }

    // 3. Website Development
    if (preg_match('/(web|website|frontend|backend|full stack|next\.js|react\.js|html|landing page)/iu', $q)) {
        return "💻 **Website Development Services:**\n\nWe build high-performance, responsive websites and enterprise web platforms using React, Next.js, TypeScript, and modern cloud technologies. Our deliverables include:\n- Custom Full-Stack Websites & Web Applications\n- Sub-second Load Times & Core Web Vitals Optimization\n- Mobile-First Responsive Design\n\nCheck out our recent web works like **Shagunshri Homestay**, **Shree Samarth Clinic**, and **Hotel BK Palace** in our Portfolio!";
    }

    // 3.1 Software Automation & AI Workflows
    if (preg_match('/(automation|automate|rpa|workflow|bot|script|pipeline|crm sync|erp sync|auto mation)/iu', $q)) {
        return "⚡ **Software Automation & AI Workflows:**\n\nWe build intelligent software automation solutions that eliminate repetitive manual tasks and cut operational costs:\n- Custom Business Workflow Automation & RPA\n- Bidirectional CRM, ERP & Invoicing Sync\n- Webhook Handlers, Data Scraping & API Pipelines\n- AI Agents & 24/7 Background Bot Automation\n\nContact our engineering team to get a free automation audit for your business!";
    }

    // 4. Mobile Apps
    if (preg_match('/(app|mobile|android|ios|flutter|react native|kotlin|play store|app store)/iu', $q)) {
        return "📱 **Mobile App Development:**\n\nWe build native and cross-platform apps for iOS and Android using Flutter, React Native, Kotlin, and Swift. Over 25+ published apps including:\n- **Appomints** (Salon & Doctor Appointments)\n- **Striker** (Live Sports & Turf Booking)\n- **Nandi Wahan & Nandi Wahan Driver** (Ride-sharing)\n- **Solanki Steel Railing** (B2B E-commerce)\n\nWe handle complete UI/UX, backend sync, and Play Store / App Store deployment!";
    }

    // 5. UI/UX Design
    if (preg_match('/(design|ui|ux|figma|prototype|wireframe)/iu', $q)) {
        return "🎨 **UI/UX Design Services:**\n\nOur design philosophy focuses on clean, conversion-driven user experiences:\n- Interactive Figma Prototypes & Wireframing\n- Scalable Design Systems & UI Kits\n- Conversion-Rate-Optimized User Journeys\n- Modern glassmorphism, micro-animations, and clean typography";
    }

    // 6. E-commerce
    if (preg_match('/(ecommerce|e-commerce|store|shop|selling|payment gateway|razorpay|stripe)/iu', $q)) {
        return "🛒 **E-Commerce Solutions:**\n\nWe develop scalable online stores with frictionless multi-currency checkouts, inventory sync, and secure payment integrations (Razorpay, Stripe, PayPal):\n- Headless & Custom Storefronts\n- Real-Time Order & Inventory Tracking\n- Automated Cart Abandonment Recovery\n\nExplore our e-commerce case studies: **Ojastra Herbal Store** and **Kanakraj Plywood**!";
    }

    // 7. Leadership & Team
    if (preg_match('/(ceo|founder|director|leadership|team|karish|dheeraj|tarun|tarunendra|who runs|owner)/iu', $q)) {
        return "👥 **Our Leadership Team:**\n\n- **Karish Chouhan** – Founder & CEO: Steering digital innovation and strategic partnerships for 3+ years.\n- **Dheeraj Solanki** – Principal Software Architect: Software architect with 10+ years in development.\n- **Tarunendra Sharma** – Head of Operations: Ensuring operational excellence and client satisfaction.";
    }

    // 8. Contact & Location
    if (preg_match('/(contact|phone|email|address|location|office|indore|call|whatsapp|reach)/iu', $q)) {
        return "📍 **Contact {$companyName}:**\n\n- **Office Address:** {$address}\n- **Phone / WhatsApp:** [{$phone}]({$whatsapp})\n- **Email:** {$email}\n\nOur team is available Monday to Saturday (9:00 AM – 7:00 PM IST). Feel free to reach out anytime!";
    }

    // 9. Portfolio & Projects
    if (preg_match('/(portfolio|project|work|case study|appomints|striker|nandi|cowsan|ojastra|kanakraj|solanki|shagunshri|samarth|pilgrim|amo|bk palace|samriya)/iu', $q)) {
        return "🚀 **Our Portfolio Highlights:**\n\n- **Shagunshri Homestay & Pilgrims Nest:** Modern hospitality & homestay booking platforms.\n- **Shree Samarth Homeo Clinic:** Healthcare & clinic doctor consultation portal.\n- **Hotel BK Palace:** Luxury hotel booking & amenities showcase.\n- **Amo Tours & Travels:** Customized tour packages & Ujjain taxi booking portal.\n- **Samriya Traders & Ojastra:** High-converting commercial & e-commerce websites.\n- **Appomints & Striker:** Leading Android mobile apps.\n\nVisit our Portfolio section to view case studies and live demos!";
    }

    // 10. Technology Stack
    if (preg_match('/(tech|technology|stack|language|framework|database|cloud|aws|react|node|flutter|php)/iu', $q)) {
        return "⚡ **Our Technology Stack:**\n\n- **Frontend / Web:** React.js, Next.js, Tailwind CSS, TypeScript, HTML5\n- **Mobile:** Flutter, React Native, Android (Kotlin), iOS (Swift)\n- **Backend & APIs:** Node.js, PHP, Python, REST APIs, GraphQL\n- **Databases & Cloud:** MySQL, PostgreSQL, MongoDB, AWS, Google Cloud, Docker";
    }

    // 11. Timeline & Duration
    if (preg_match('/(time|duration|how long|timeline|kab tak|din)/iu', $q)) {
        return "⏱️ **Project Timelines:**\n\n- **Standard Websites / Landing Pages:** 2–4 weeks\n- **Complex Web Applications / E-Commerce:** 4–8 weeks\n- **Mobile Applications:** 2–4 months\n\nWe define clear milestones and provide weekly progress demos!";
    }

    // 12. Source Code & Ownership
    if (preg_match('/(source code|code|ownership|ip|intellectual property|rights)/iu', $q)) {
        return "🔒 **100% Source Code Ownership:**\n\nYes! Once the project is completed, all intellectual property, source code, repositories, and credentials belong 100% to you.";
    }

    // Default Fallback
    return "Thank you for your question! 😊\n\nAt **{$companyName}**, we specialize in **Custom Web Development, Mobile Apps (Android & iOS), UI/UX Design, and E-Commerce Solutions**.\n\nWould you like to:\n1. 📱 Explore our Portfolio case studies\n2. 💬 Chat directly with our engineers on [WhatsApp]({$whatsapp})\n3. 📩 Request a free quote via our Contact Form?";
}

/**
 * Call Gemini AI API via cURL
 */
function callGeminiAI($apiKey, $userMessage) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($apiKey);

    $systemPrompt = "You are Victorious Assistant, an AI representative for Victorious Innovatech Solutions.\n"
                  . "Company: " . COMPANY_NAME . "\n"
                  . "Tagline: " . COMPANY_TAGLINE . "\n"
                  . "Services: Web Development, Mobile Apps (iOS/Android), UI/UX, E-commerce, Cloud Solutions\n"
                  . "Address: " . COMPANY_ADDRESS . "\n"
                  . "Phone: " . COMPANY_PHONE . "\n"
                  . "Email: " . COMPANY_EMAIL . "\n"
                  . "Stay strictly within the company's scope. Keep answers concise, professional, and friendly.\n"
                  . "User asked: \"" . addslashes($userMessage) . "\"";

    $payload = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $systemPrompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        }
    }

    return null;
}

// 1. Try Gemini AI if API key is provided
$botReply = null;
$engineUsed = 'local_ai';

if (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) && GEMINI_API_KEY !== 'MY_GEMINI_API_KEY') {
    $botReply = callGeminiAI(GEMINI_API_KEY, $userMessage);
    if ($botReply !== null) {
        $engineUsed = 'gemini';
    }
}

// 2. Fallback to built-in intelligent knowledge engine
if ($botReply === null) {
    $botReply = generateLocalAIResponse($userMessage);
    $engineUsed = 'local_ai';
}

// 3. Client IP address
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ipAddress = trim($ipList[0]);
}

// 4. Log interaction if enabled
if (defined('LOG_CHAT_MESSAGES') && LOG_CHAT_MESSAGES) {
    try {
        $pdo = getDbConnection();
        if ($pdo) {
            $logStmt = $pdo->prepare("INSERT INTO chat_logs (user_message, bot_response, engine, ip_address) VALUES (:msg, :reply, :engine, :ip)");
            $logStmt->execute([
                ':msg'    => $userMessage,
                ':reply'  => $botReply,
                ':engine' => $engineUsed,
                ':ip'     => $ipAddress
            ]);
        }
    } catch (Exception $e) {
        error_log("Chat Log Warning: " . $e->getMessage());
    }

    // CSV Fallback log
    $csvFile = __DIR__ . '/chat_logs_backup.csv';
    $fileExisted = file_exists($csvFile);
    if ($fp = @fopen($csvFile, 'a')) {
        if (!$fileExisted) {
            fputcsv($fp, ['Date', 'User Message', 'Bot Reply', 'Engine', 'IP']);
        }
        fputcsv($fp, [date('Y-m-d H:i:s'), $userMessage, $botReply, $engineUsed, $ipAddress]);
        fclose($fp);
    }
}

// 5. Send JSON response
http_response_code(200);
echo json_encode([
    'success' => true,
    'engine'  => $engineUsed,
    'message' => [
        'role'    => 'assistant',
        'content' => $botReply
    ]
]);
