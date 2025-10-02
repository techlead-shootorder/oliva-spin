<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spinning Wheel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .wheel-container {
            position: relative;
            width: 100%;
            padding-top: 100%; /* 1:1 Aspect Ratio */
            margin: 0 auto;
            will-change: transform;
        }
        
        .wheel {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            overflow: hidden;
            transition: transform 4s cubic-bezier(0.17, 0.67, 0.12, 0.99);
            box-shadow: 0 12px 40px rgba(102, 126, 234, 0.3), 0 0 0 4px rgba(102, 126, 234, 0.2), 0 0 0 6px rgba(102, 126, 234, 0.1);
            border: 2px solid rgba(102, 126, 234, 0.4);
            will-change: transform;
        }
        
        .wheel-segment {
            position: absolute;
            width: 50%;
            height: 50%;
            transform-origin: 100% 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 11px;
            color: white;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            cursor: pointer;
        }
        
        .wheel-segment .segment-text {
            position: absolute;
            top: 40%;
            left: 47%;
            transform-origin: center;
            white-space: nowrap;
            font-size: inherit;
            font-weight: inherit;
            color: inherit;
            text-shadow: inherit;
            pointer-events: none;
            user-select: none;
            text-align: center;
            line-height: 1.2;
        }
        
        @media (min-width: 640px) {
            .wheel-segment {
                font-size: 12px;
            }
        }
        
        @media (min-width: 768px) {
            .wheel-segment {
                font-size: 14px;
            }
        }
        
        .wheel-pointer {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 16px solid transparent;
            border-right: 16px solid transparent;
            border-top: 32px solid #667eea;
            z-index: 20;
            filter: drop-shadow(0 6px 12px rgba(102, 126, 234, 0.4));
        }
        
        .wheel-pointer::after {
            content: '';
            position: absolute;
            top: -28px;
            left: -12px;
            width: 0;
            height: 0;
            border-left: 12px solid transparent;
            border-right: 12px solid transparent;
            border-top: 24px solid #ffffff;
        }
        
        .spin-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: 6px solid rgba(255, 255, 255, 0.9);
            z-index: 15;
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4), 0 0 0 2px rgba(102, 126, 234, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            line-height: 1.1;
        }

        .spin-handle {
            position: absolute;
            top: 35%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120px;
            height: 120px;
            background-image: url('images/Spin-Handle.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            z-index: 16;
            pointer-events: none;
        }
        
        @media (min-width: 640px) {
            .spin-button {
                width: 90px;
                height: 90px;
                font-size: 13px;
            }
            .spin-handle {
                width: 130px;
                height: 130px;
            }
        }
        
        @media (min-width: 768px) {
            .spin-button {
                width: 100px;
                height: 100px;
                font-size: 14px;
            }
            .spin-handle {
                width: 140px;
                height: 140px;
            }
        }
        
        .spin-button:hover:not(:disabled) {
            transform: translate(-50%, -50%) scale(1.05);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.25);
        }
        
        .spin-button:active:not(:disabled) {
            transform: translate(-50%, -50%) scale(0.95);
        }
        
        .spin-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); }
            50% { transform: translate(-50%, -50%) scale(1.02); }
        }
        
        .floating-animation {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .result-card {
            background: #01a4a6;
            border-radius: 20px;
            padding: 24px;
            color: white;
            text-align: center;
            box-shadow: 0 12px 40px rgba(102, 126, 234, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.3) inset;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(15px);
            position: relative;
            overflow: hidden;
        }
        
        .result-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, transparent 50%, rgba(255, 255, 255, 0.1) 100%);
            pointer-events: none;
        }
        
        .option-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.95) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15);
        }
        
        .input-group {
            position: relative;
            margin-bottom: 16px;
        }
        
        .input-field {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
        }
        
        .input-field:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .add-button {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
        }
        
        .add-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
        }
        
        .option-tag {
            display: inline-block;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 20px;
            padding: 6px 12px;
            margin: 4px;
            font-size: 12px;
            font-weight: 500;
            color: #374151;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .glass-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 252, 0.9) 100%);
            backdrop-filter: blur(25px);
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 28px;
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            will-change: transform;
        }
    </style>
</head>
<!-- <body class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50" > -->
<body class="min-h-screen object-cover" style="background: url('images/BG.png')" >

    <div class="min-h-screen flex flex-col items-center justify-center p-4 space-y-6">
         
        <!-- Phone Number Login Modal -->
        <div id="phoneLoginModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
            <div class="glass-card p-6 w-full max-w-sm">
                <div class="text-center mb-6">
                   
                    <h2 class="text-xl font-bold text-gray-800 mb-2">Enter Your Details</h2>
                    <p class="text-gray-600 text-sm">Please enter your name and phone number to continue</p>
                </div>
                
                <form id="phoneLoginForm" class="space-y-4">
                    <div>
                        <input 
                            type="text" 
                            id="nameInput" 
                            placeholder="Enter your full name"
                            class="input-field"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Enter your full name</p>
                    </div>
                    
                    <div>
                        <input 
                            type="tel" 
                            id="phoneInput" 
                            placeholder="Enter your phone number"
                            class="input-field"
                            pattern="[0-9]{10}"
                            maxlength="10"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Enter 10-digit phone number</p>
                    </div>

                    <div id="phoneError" class="text-red-500 text-sm my-2 text-center"></div>
                    
                    <button type="submit" class="add-button w-full">
                        Continue to Spin Wheel
                    </button>
                </form>
            </div>
        </div>

        <!-- OTP Verification Modal -->
        <div id="otpModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
            <div class="glass-card p-6 w-full max-w-sm">
                <div class="text-center mb-6">
                    
                    <h2 class="text-xl font-bold text-gray-800 mb-2">Verify OTP</h2>
                    <p class="text-gray-600 text-sm">An OTP has been sent to your number. Please enter it below.</p>
                </div>
                
                <form id="otpVerifyForm" class="space-y-4">
                    <div>
                        <input 
                            type="text" 
                            id="otpInput" 
                            placeholder="Enter OTP"
                            class="input-field"
                            pattern="[0-9]{4}"
                            maxlength="4"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Enter the 4-digit OTP.</p>
                    </div>
                    
                    <button type="submit" class="add-button w-full">
                        Verify & Spin
                    </button>
                </form>
            </div>
        </div>

        <!-- SMS Error Modal -->
        <div id="smsErrorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
            <div class="glass-card p-6 w-full max-w-sm">
                <div class="text-center mb-6">
                    
                    <h2 class="text-xl font-bold text-red-600 mb-2">SMS Not Sent</h2>
                    <p class="text-gray-600 text-sm" id="smsErrorMessage">There was an error sending the SMS with your coupon code.</p>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800">
                            <strong>Don't worry!</strong> Your coupon code is still valid. Please take a screenshot of this page or note down your coupon code.
                        </p>
                    </div>
                    
                    <button onclick="hideSmsErrorModal()" class="add-button w-full">
                        Got it, Continue
                    </button>
                </div>
            </div>
        </div>

        <!-- Header -->
        <div class="text-center mb-2">
            <img src="images/oliva-logo.png" 
                 alt="Oasis India Logo" 
                 class="mx-auto h-12 sm:h-16 md:h-20 w-auto mb-10">

                 <img src="images/Text.png" 
                 alt="Oasis India Logo" 
                 class="mx-auto mb-4 h-12 sm:h-16 md:h-40 w-auto">
            <!-- <h1 id="wheelTitle" class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800 mb-2">
                Oasis Spin Wheel's
            </h1>
         
            <p id="wheelDescription" class="text-gray-600 text-sm sm:text-base">
                Spin once and win amazing discounts on IVF treatments!
            </p> -->
        </div>
        
        <!-- Wheel Container -->
        <div class="w-full max-w-md mx-auto">
            <div class="wheel-container floating-animation">
                <div class="wheel-pointer"></div>
                <div id="wheel" class="wheel">
                    <!-- Wheel segments will be generated by JavaScript -->
                </div>
                <button 
                    id="spinBtn" 
                    class="spin-button"
                    onclick="spinWheel()"
                >
                    <span id="spinText"><br>SPIN</span>
                </button>
                <div class="spin-handle"></div>
            </div>
        </div>
        
        <!-- Result -->
        <div id="result" class="result-card hidden w-full max-w-sm transform transition-all duration-500 scale-95 opacity-0">
            <p class="text-sm font-medium text-white/80 mb-2">Registered Phone: <span id="resultRecordedId" class="font-bold"></span></p>
            
            <h3 class="text-xl  mb-3 relative z-10">Congratulations! <span class="font-bold"> Unlocked:</span></h3>
            <p class="text-2xl font-bold mb-4 relative z-10" id="resultText"></p>


            <div class="mt-4 p-5 bg-white rounded-xl backdrop-blur-sm relative z-10" style="box-shadow: 8px 8px 0px #E89B3B">
                <p class="text-sm font-semibold mb-2 text-black">Your Coupon Code:</p>
                <p class="text-xl font-mono font-bold tracking-wider" style="color: #01a4a6" id="couponCode"></p>
            </div>
            <div class="mt-4 text-xs text-white/90 font-medium space-y-1">
                <p>Show the code at the clinic billing counter to avail the offer.</p>
            </div>
            <div class="mt-6 w-16 h-1 bg-white/40 rounded-full mx-auto relative z-10"></div>
        </div>
        
        <!-- Spin Status -->
        <div id="spinStatus" class="w-full max-w-sm hidden">
            <div class="option-card">
                <p class="text-sm font-medium text-gray-600 mb-2">Registered Phone: <span id="statusRecordedId" class="font-bold"></span></p>
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <span class="mr-2">🎯</span>
                    Your Spin Result
                </h3>
                <div class="text-center">
                    <p class="text-sm text-gray-600 mb-2">You have already spun and won:</p>
                    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-5 border border-indigo-200">
                        <p class="text-lg font-bold text-indigo-800" id="previousResult"></p>
                        <div class="mt-4 p-4 bg-white from-emerald-50 to-teal-50 rounded-xl border-2 border-emerald-200 shadow-sm">
                            <p class="text-sm text-emerald-700 font-semibold mb-2">Your Coupon Code:</p>
                            <p class="text-lg font-mono font-bold text-emerald-800 tracking-wider" id="previousCouponCode"></p>
                        </div>
                        <p class="text-sm text-indigo-600 mt-3 font-medium">Thank you for participating!</p>
                    </div>
                </div>
            </div>
        </div>
        
        
        <!-- Segment Management -->
     
        
        

        <!-- Footer -->
        <div class="text-center text-xs text-gray-500 mt-8">
            <p>
                <a href="https://www.olivaclinic.com/privacy-policy/" target="_blank" class="text-blue-600 hover:underline">Privacy Policy</a> |
                <a href="https://www.olivaclinic.com/terms-conditions/" target="_blank" class="text-blue-600 hover:underline">Terms & Conditions</a>
            </p>
        </div>
    </div>

    <script>
        // Get URL parameters
        function getUrlParameter(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }
        
        // Capture UTM parameters
        function captureUtmParameters() {
            return {
                utm_source: getUrlParameter('utm_source') || '',
                utm_medium: getUrlParameter('utm_medium') || '',
                utm_campaign: getUrlParameter('utm_campaign') || '',
                utm_term: getUrlParameter('utm_term') || '',
                utm_content: getUrlParameter('utm_content') || ''
            };
        }
        
        // Detect browser and OS
        function getBrowserInfo() {
            const userAgent = navigator.userAgent;
            let browser = 'Unknown';
            let os = 'Unknown';
            
            // Detect browser
            if (userAgent.indexOf('Chrome') > -1 && userAgent.indexOf('Edg') === -1) {
                browser = 'Chrome';
            } else if (userAgent.indexOf('Firefox') > -1) {
                browser = 'Firefox';
            } else if (userAgent.indexOf('Safari') > -1 && userAgent.indexOf('Chrome') === -1) {
                browser = 'Safari';
            } else if (userAgent.indexOf('Edg') > -1) {
                browser = 'Edge';
            } else if (userAgent.indexOf('Opera') > -1 || userAgent.indexOf('OPR') > -1) {
                browser = 'Opera';
            }
            
            // Detect OS
            if (userAgent.indexOf('Windows') > -1) {
                os = 'Windows';
            } else if (userAgent.indexOf('Mac') > -1) {
                os = 'macOS';
            } else if (userAgent.indexOf('Linux') > -1) {
                os = 'Linux';
            } else if (userAgent.indexOf('Android') > -1) {
                os = 'Android';
            } else if (userAgent.indexOf('iPhone') > -1 || userAgent.indexOf('iPad') > -1) {
                os = 'iOS';
            }
            
            return {
                browser: browser,
                os: os,
                prev_url: document.referrer || ''
            };
        }
        
        // Store UTM parameters and browser info for later use
        const utmParameters = captureUtmParameters();
        const browserInfo = getBrowserInfo();
        
        // Get recorded ID from URL or session storage
        let recordedId = getUrlParameter('recordedId');
        
        // Wheel options will be loaded from API
        let wheelOptions = [];
        let canSpin = false; // Default to false, only API can enable
        let isSpinning = false;
        let currentWeek = 1;
        let currentWheelRotation = 0; // Track cumulative wheel rotation
        
        // Generate vibrant colors
        function getRandomColor() {
            const colors = [
                "#ef4444", "#f97316", "#eab308", "#22c55e", 
                "#3b82f6", "#8b5cf6", "#ec4899", "#06b6d4",
                "#84cc16", "#f59e0b", "#10b981", "#6366f1",
                "#f43f5e", "#8b5cf6", "#0ea5e9", "#ef4444"
            ];
            return colors[Math.floor(Math.random() * colors.length)];
        }
        
        function createWheel() {
            const wheel = document.getElementById('wheel');
            wheel.innerHTML = '';
            
            // Create SVG for perfect pie segments - SAME UI as original
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.style.width = '100%';
            svg.style.height = '100%';
            svg.style.position = 'absolute';
            svg.style.top = '0';
            svg.style.left = '0';
            svg.setAttribute('viewBox', '0 0 200 200');
            
            const centerX = 100;
            const centerY = 100;
            const radius = 90;
            let currentAngle = 0;
            
            // Calculate equal segment size for visual fairness - SAME as original
            const segmentAngle = 360 / wheelOptions.length;
            
            wheelOptions.forEach((option, index) => {
                // Create SVG path for pie segment with equal sizes - SAME as original
                const startAngle = currentAngle;
                const endAngle = currentAngle + segmentAngle;
                
                // Debug: Log segment creation - SAME as original
                console.log(`🎨 Creating segment ${index}:`, {
                    text: option.text,
                    startAngle: startAngle.toFixed(1),
                    endAngle: endAngle.toFixed(1),
                    centerAngle: (startAngle + segmentAngle/2).toFixed(1),
                    visualStart: (startAngle - 90).toFixed(1),
                    visualEnd: (endAngle - 90).toFixed(1)
                });
                
                // Convert to radians - SAME as original
                const startAngleRad = (startAngle - 90) * Math.PI / 180;
                const endAngleRad = (endAngle - 90) * Math.PI / 180;
                
                // Calculate start and end points - SAME as original
                const startX = centerX + radius * Math.cos(startAngleRad);
                const startY = centerY + radius * Math.sin(startAngleRad);
                const endX = centerX + radius * Math.cos(endAngleRad);
                const endY = centerY + radius * Math.sin(endAngleRad);
                
                // Create path - SAME as original
                const largeArcFlag = segmentAngle > 180 ? 1 : 0;
                const pathData = [
                    `M ${centerX} ${centerY}`,
                    `L ${startX} ${startY}`,
                    `A ${radius} ${radius} 0 ${largeArcFlag} 1 ${endX} ${endY}`,
                    'Z'
                ].join(' ');
                
                // Create gradient for each segment - SAME as original
                const gradientId = `gradient-${index}`;
                const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
                gradient.setAttribute('id', gradientId);
                gradient.setAttribute('x1', '0%');
                gradient.setAttribute('y1', '0%');
                gradient.setAttribute('x2', '100%');
                gradient.setAttribute('y2', '100%');
                
                const stop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
                stop1.setAttribute('offset', '0%');
                stop1.setAttribute('stop-color', option.color);
                stop1.setAttribute('stop-opacity', '1');
                
                const stop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
                stop2.setAttribute('offset', '100%');
                // Darken the color for gradient effect - SAME as original
                const darkColor = option.color.replace(/[^,]+(?=\))/, (match) => (parseFloat(match) * 0.7).toString());
                stop2.setAttribute('stop-color', darkColor.includes('rgba') ? darkColor : `${option.color}aa`);
                stop2.setAttribute('stop-opacity', '1');
                
                gradient.appendChild(stop1);
                gradient.appendChild(stop2);
                
                // Add gradient to defs - SAME as original
                let defs = svg.querySelector('defs');
                if (!defs) {
                    defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
                    svg.appendChild(defs);
                }
                defs.appendChild(gradient);
                
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', pathData);
                path.setAttribute('fill', `url(#${gradientId})`);
                path.setAttribute('stroke', 'rgba(255, 255, 255, 0.8)');
                path.setAttribute('stroke-width', '2');
                path.style.cursor = 'pointer';
                path.style.filter = 'drop-shadow(0 2px 4px rgba(0,0,0,0.1))';
                
                svg.appendChild(path);
                
                // Add text label with proper game design - SAME as original
                const textAngle = startAngle + segmentAngle / 2;
                const textRadius = radius * 0.65; // Closer to center for better fit
                const textAngleRad = (textAngle - 90) * Math.PI / 180;
                const textX = centerX + textRadius * Math.cos(textAngleRad);
                const textY = centerY + textRadius * Math.sin(textAngleRad);
                
                // Create text with smart line breaking for longer text - SAME as original
                const textContent = option.text;
                const words = textContent.split(' ');
                
                if (words.length > 1 && textContent.length > 8) {
                    // Multi-line text for longer labels - SAME as original
                    const textGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                    textGroup.setAttribute('transform', `rotate(${textAngle}, ${textX}, ${textY})`);
                    
                    words.forEach((word, wordIndex) => {
                        const line = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                        line.setAttribute('x', textX);
                        line.setAttribute('y', textY + (wordIndex - (words.length - 1) / 2) * 10);
                        line.setAttribute('text-anchor', 'middle');
                        line.setAttribute('dominant-baseline', 'middle');
                        line.setAttribute('fill', 'white');
                        line.setAttribute('font-size', '9');
                        line.setAttribute('font-weight', '700');
                        line.setAttribute('font-family', '"Segoe UI", Tahoma, Geneva, Verdana, sans-serif');
                        line.setAttribute('stroke', 'rgba(0,0,0,0.3)');
                        line.setAttribute('stroke-width', '0.5');
                        line.style.pointerEvents = 'none';
                        line.style.userSelect = 'none';
                        line.textContent = word;
                        textGroup.appendChild(line);
                    });
                    
                    svg.appendChild(textGroup);
                } else {
                    // Single line text - SAME as original
                    const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                    text.setAttribute('x', textX);
                    text.setAttribute('y', textY);
                    text.setAttribute('text-anchor', 'middle');
                    text.setAttribute('dominant-baseline', 'middle');
                    text.setAttribute('fill', 'white');
                    text.setAttribute('font-size', textContent.length > 10 ? '8' : '10');
                    text.setAttribute('font-weight', '700');
                    text.setAttribute('font-family', '"Segoe UI", Tahoma, Geneva, Verdana, sans-serif');
                    text.setAttribute('stroke', 'rgba(0,0,0,0.3)');
                    text.setAttribute('stroke-width', '0.5');
                    text.setAttribute('transform', `rotate(${textAngle}, ${textX}, ${textY})`);
                    text.style.pointerEvents = 'none';
                    text.style.userSelect = 'none';
                    text.textContent = textContent;
                    
                    svg.appendChild(text);
                }
                
                currentAngle += segmentAngle;
            });
            
            wheel.appendChild(svg);
        }
        
        // Load wheel data from API
        async function loadWheelData() {
            try {
                const response = await fetch(`api/get-wheel-data.php?recordedId=${recordedId}`);
                const data = await response.json();
                
                if (data.success) {
                    wheelOptions = data.wheelData;
                    canSpin = data.canSpin;
                    currentWeek = data.currentWeek;
                    
                    // Helper to safely set text content
                    const setText = (id, text) => {
                        const el = document.getElementById(id);
                        if (el) {
                            el.textContent = text;
                        } else {
                            console.warn(`Element with ID '${id}' not found.`);
                        }
                    };

                    // Update UI elements
                    setText('wheelTitle', data.settings.wheel_title || '🎯 Oasis Spin Wheel');
                    setText('wheelDescription', data.settings.wheel_description || 'Spin once and win amazing discounts on IVF treatments!');
                    setText('currentWeek', currentWeek);
                    
                    // Show previous result ONLY if user already spun AND has previous result
                    if (!canSpin && data.previousResult) {
                        setText('previousResult', data.previousResult);
                        setText('statusRecordedId', recordedId);
                        
                        if (data.previousCouponCode) {
                            setText('previousCouponCode', data.previousCouponCode);
                        } else {
                            setText('previousCouponCode', 'Code not available');
                        }
                        
                        // Hide the wheel container completely
                        const wheelContainer = document.querySelector('.wheel-container');
                        if (wheelContainer) {
                            wheelContainer.style.display = 'none';
                        }
                        
                        // Show only the main result card, hide the status card
                        const resultTextMapping = {
                            "Vdiscover @499": "Vdiscover 5 Step Analysis & Consultation @ Rs <s>900</s> 499",
                            "Free Vdiscover": "FREE Vdiscover 5 Step Analysis & Consultation",
                            "Extra 10% off": "Additional 10% off on bill - Enjoy total discounts up to 40%!",
                            "Extra 15% off": "Additional 15% off on bill - Enjoy total discounts up to 45%!",
                            "Extra 20% off": "Additional 20% off on bill - Enjoy total discounts up to 50%!"
                        };

                        const resultText = resultTextMapping[data.previousResult] || data.previousResult;
                        
                        // Update and show main result display
                        document.getElementById('resultRecordedId').textContent = recordedId;
                        document.getElementById('resultText').innerHTML = resultText;
                        document.getElementById('couponCode').textContent = data.previousCouponCode || 'N/A';
                        
                        // Show result with animation
                        const result = document.getElementById('result');
                        result.classList.remove('hidden');
                        setTimeout(() => {
                            result.classList.remove('scale-95', 'opacity-0');
                            result.classList.add('scale-100', 'opacity-100');
                        }, 50);

                    } else if (canSpin) {
                        // User can spin - make sure button is enabled
                        const spinBtnEl = document.getElementById('spinBtn');
                        if (spinBtnEl) spinBtnEl.disabled = false;

                        const spinTextEl = document.getElementById('spinText');
                        if (spinTextEl) spinTextEl.innerHTML = '🎲<br>SPIN';
                    }
                    
                    createWheel();
                } else {
                    console.error('Failed to load wheel data:', data.error);
                    loadFallbackData();
                }
            } catch (error) {
                console.error('Error loading wheel data:', error);
                loadFallbackData();
            }
        }
        
        // Fallback data when API is not available
        function loadFallbackData() {
            console.log('🔄 Loading fallback data for testing...');
            wheelOptions = [
                { text: "Vdiscover @499", color: "#038b91", probability: 20, code: "SAVE10K" },
                { text: "Free Vdiscover", color: "#00b3b5", probability: 18, code: "SAVE15K" },
                { text: "Extra 10% off", color: "#038b91", probability: 16, code: "SAVE20K" },
                { text: "Extra 15% off", color: "#00b3b5", probability: 14, code: "SAVE50K" },
                { text: "Extra 20% off", color: "#038b91", probability: 12, code: "SAVE100K" },
                { text: "Better luck next time", color: "#00b3b5", probability: 10, code: "FREEIVF" }
            ];
            canSpin = true; // Allow spinning with fallback data
            
            // Enable spin button
            document.getElementById('spinBtn').disabled = false;
            document.getElementById('spinText').innerHTML = '🎲<br>SPIN';
            
            createWheel();
        }
        
        // Spin the wheel
        async function spinWheel() {
            if (isSpinning || !canSpin) return;
            
            if (!recordedId) {
                showPhoneLoginModal();
                return;
            }
            
            // Debug: Log what we have in sessionStorage
            console.log('About to spin - tempUserName:', sessionStorage.getItem('tempUserName'));
            
            isSpinning = true;
            const spinBtn = document.getElementById('spinBtn');
            const spinText = document.getElementById('spinText');
            const result = document.getElementById('result');
            const wheel = document.getElementById('wheel');
            
            // Hide previous result with animation
            result.classList.add('hidden');
            result.classList.remove('scale-100', 'opacity-100');
            result.classList.add('scale-95', 'opacity-0');
            
            // Update button state
            spinBtn.disabled = true;
            spinText.innerHTML = '🌀<br>SPINNING';
            
            try {
                let winner;
                
                // Try API first, fallback to local selection if needed
                try {
                    const requestData = {
                        recordedId: recordedId,
                        userName: sessionStorage.getItem('tempUserName') || ''
                    };
                    console.log('Sending spin request with data:', requestData);
                    
                    const response = await fetch('api/spin.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(requestData)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        winner = data.result;
                    } else {
                        throw new Error(data.error || 'API returned error');
                    }
                } catch (apiError) {
                    console.log('🔄 API not available, using fallback selection:', apiError.message);
                    // Fallback: randomly select from wheelOptions
                    const randomIndex = Math.floor(Math.random() * wheelOptions.length);
                    winner = wheelOptions[randomIndex];
                    winner.code = winner.code || `FALLBACK${Date.now()}`;
                }
                
                if (winner) {

                    // --- DYNAMIC ROTATION LOGIC ---

                    // 1. Find the index of the winner in the wheelOptions array, which matches the visual layout
                    const winnerIndex = wheelOptions.findIndex(option => option.text === winner.text);

                    if (winnerIndex === -1) {
                        // This should not happen if data from get-wheel-data.php and spin.php are consistent
                        console.error("Winning prize not found in wheel options!", winner);
                        // Fallback to a random spin
                        wheel.style.transform = `rotate(${360 * 5 + Math.random() * 360}deg)`;
                    } else {
                        // 2. Calculate the rotation
                        const segmentCount = wheelOptions.length;
                        const segmentAngle = 360 / segmentCount;
                        
                        // The wheel is drawn with 0 degrees at the top. We want to land on the middle of the segment.
                        const winningAngle = (winnerIndex * segmentAngle) + (segmentAngle / 2);

                        // Add a small random offset within the segment to make it look less robotic
                        const randomOffset = (Math.random() - 0.5) * (segmentAngle * 0.8);

                        // Add multiple full rotations for animation effect. 
                        // The final rotation should align the winning angle with the top pointer (0 degrees).
                        const totalRotation = (360 * 5) - winningAngle - randomOffset;

                        console.log('🎯 Dynamic Rotation Debug:', {
                            winner: winner.text,
                            winnerIndex: winnerIndex,
                            segmentAngle: segmentAngle.toFixed(1),
                            winningAngle: winningAngle.toFixed(1),
                            totalRotation: totalRotation.toFixed(1)
                        });

                        // Apply rotation
                        wheel.style.transform = `rotate(${totalRotation}deg)`;
                    }

                    // --- END OF DYNAMIC ROTATION LOGIC ---

                    // Show result after animation
                    setTimeout(() => {
                        const resultTextMapping = {
                            "Vdiscover @499": "Vdiscover 5 Step Analysis & Consultation @ Rs <s>900</s> 499",
                            "Free Vdiscover": "FREE Vdiscover 5 Step Analysis & Consultation",
                            "Extra 10% off": "Additional 10% off on bill - Enjoy total discounts up to 40%!",
                            "Extra 15% off": "Additional 15% off on bill - Enjoy total discounts up to 45%!",
                            "Extra 20% off": "Additional 20% off on bill - Enjoy total discounts up to 50%!"
                        };

                        const resultText = resultTextMapping[winner.text] || winner.text;

                        // Update result display
                        document.getElementById('resultRecordedId').textContent = recordedId;
                        document.getElementById('resultText').innerHTML = resultText;
                        document.getElementById('couponCode').textContent = winner.code;
                        
                        // Send SMS
                        sendSms(recordedId, winner.code);

                        // Create lead in Zoho CRM
                        const storedUserName = sessionStorage.getItem('tempUserName') || '';
                        console.log('Creating lead with userName:', storedUserName);
                        createLeadInZoho(recordedId, winner.code, winner.text, storedUserName);
                        
                        // Clear stored name after use
                        sessionStorage.removeItem('tempUserName');

                        // Show result with animation
                        result.classList.remove('hidden');
                        setTimeout(() => {
                            result.classList.remove('scale-95', 'opacity-0');
                            result.classList.add('scale-100', 'opacity-100');
                        }, 50);
                        
                        // Disable further spins
                        canSpin = false;
                        spinText.innerHTML = '✓<br>USED';
                        
                        // Add celebration effect
                        createConfetti();
                        
                        // Show spin status
                        document.getElementById('previousResult').textContent = winner.text;
                        document.getElementById('previousCouponCode').textContent = winner.code;
                        
                        isSpinning = false;
                    }, 4000);
                } else {
                    throw new Error(data.error || 'Spin failed');
                }
            } catch (error) {
                console.error('Spin error:', error);
                alert(error.message || 'An error occurred while spinning. Please try again.');
                
                // Re-enable button on error
                spinBtn.disabled = false;
                spinText.innerHTML = '🎲<br>SPIN';
                isSpinning = false;
            }
        }

        async function sendSms(mobile, couponCode) {
            try {
                const response = await fetch('api/send-sms.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        mobile: mobile,
                        couponCode: couponCode
                    }),
                });

                const result = await response.json();

                if (!result.success) {
                    // Display custom error modal instead of alert
                    showSmsErrorModal('There was an error sending the SMS: ' + result.error);
                }
            } catch (error) {
                console.error('Error sending SMS:', error);
                // Display custom error modal instead of alert
                showSmsErrorModal('An error occurred while sending the SMS. Please try again.');
            }
        }

        async function createLeadInZoho(mobile, couponCode, prize, userName = '') {
            try {
                const response = await fetch('api/create-lead.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        mobile: mobile,
                        couponCode: couponCode,
                        prize: prize,
                        user_name: userName,
                        utm_source: utmParameters.utm_source,
                        utm_medium: utmParameters.utm_medium,
                        utm_campaign: utmParameters.utm_campaign,
                        utm_term: utmParameters.utm_term,
                        utm_content: utmParameters.utm_content,
                        browser: browserInfo.browser,
                        os: browserInfo.os,
                        prev_url: browserInfo.prev_url
                    }),
                });

                const result = await response.json();

                if (result.success) {
                    console.log('Lead created/updated successfully:', result);
                } else {
                    console.error('Failed to create/update lead:', result.error);
                }
            } catch (error) {
                console.error('Error creating lead in Zoho:', error);
            }
        }
        
        // Simple confetti effect
        function createConfetti() {
            const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#3b82f6', '#8b5cf6'];
            
            for (let i = 0; i < 30; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = '8px';
                confetti.style.height = '8px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.borderRadius = '50%';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.top = '-10px';
                confetti.style.pointerEvents = 'none';
                confetti.style.animation = `fall ${Math.random() * 2 + 1}s linear forwards`;
                confetti.style.zIndex = '1000';
                
                document.body.appendChild(confetti);
                
                setTimeout(() => {
                    confetti.remove();
                }, 3000);
            }
        }
        
        // Add fall animation for confetti
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fall {
                to {
                    transform: translateY(100vh) rotate(360deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Update button state on load
        updateSpinButton();
        
        // Prevent zoom on double tap for mobile
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
        
        // Add visual feedback for disabled state
        function updateSpinButton() {
            const spinBtn = document.getElementById('spinBtn');
            const spinText = document.getElementById('spinText');
            
            if (!canSpin) {
                spinBtn.disabled = true;
                spinText.innerHTML = '✓<br>USED';
                spinBtn.style.opacity = '0.5';
            }
        }
        
        // Segment management functions
        function generateSegmentInputs() {
            const container = document.getElementById('segmentInputs');
            if (!container) {
                console.log('segmentInputs container not found, skipping...');
                return;
            }
            container.innerHTML = '';
            
            wheelOptions.forEach((option, index) => {
                const inputGroup = document.createElement('div');
                inputGroup.className = 'bg-gray-50 p-3 rounded-lg space-y-2';
                inputGroup.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Segment ${index + 1}</span>
                        <button 
                            onclick="removeSegment(${index})" 
                            class="text-red-500 hover:text-red-700 px-2 py-1 rounded"
                            title="Remove segment"
                        >
                            🗑️
                        </button>
                    </div>
                    <div class="space-y-2">
                        <input 
                            type="text" 
                            value="${option.text}" 
                            placeholder="Segment text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            data-index="${index}"
                            data-field="text"
                        >
                        <div class="flex space-x-2">
                            <div class="flex-1">
                                <label class="block text-xs text-gray-600 mb-1">Color</label>
                                <input 
                                    type="color" 
                                    value="${option.color}" 
                                    class="w-full h-8 border border-gray-300 rounded cursor-pointer"
                                    data-index="${index}"
                                    data-field="color"
                                >
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-600 mb-1">Text Rotation (deg)</label>
                                <input 
                                    type="number" 
                                    value="${option.textRotation || 60}" 
                                    min="0" 
                                    max="360"
                                    placeholder="60"
                                    class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    data-index="${index}"
                                    data-field="textRotation"
                                >
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(inputGroup);
            });
        }
        
        function addSegment() {
            const colors = ["#ef4444", "#f97316", "#eab308", "#22c55e", "#3b82f6", "#8b5cf6", "#ec4899", "#06b6d4"];
            const randomColor = colors[Math.floor(Math.random() * colors.length)];
            
            wheelOptions.push({
                text: `New Segment ${wheelOptions.length + 1}`,
                color: randomColor,
                value: 1000,
                code: `CODE${wheelOptions.length + 1}`,
                probability: 10,
                textRotation: 60
            });
            
            generateSegmentInputs();
        }
        
        function removeSegment(index) {
            if (wheelOptions.length > 2) {
                wheelOptions.splice(index, 1);
                generateSegmentInputs();
            } else {
                alert('Minimum 2 segments required');
            }
        }
        
        function updateWheel() {
            // Update wheelOptions from inputs
            const textInputs = document.querySelectorAll('input[data-field="text"]');
            const colorInputs = document.querySelectorAll('input[data-field="color"]');
            const rotationInputs = document.querySelectorAll('input[data-field="textRotation"]');
            
            textInputs.forEach((input, index) => {
                if (wheelOptions[index]) {
                    wheelOptions[index].text = input.value || `Segment ${index + 1}`;
                }
            });
            
            colorInputs.forEach((input, index) => {
                if (wheelOptions[index]) {
                    wheelOptions[index].color = input.value;
                }
            });
            
            rotationInputs.forEach((input, index) => {
                if (wheelOptions[index]) {
                    wheelOptions[index].textRotation = parseInt(input.value) || 60;
                }
            });
            
            // Recreate the wheel
            createWheel();
            alert('Wheel updated successfully!');
        }
        
        // New functions for enhanced features
        function resetToDefaults() {
            if (confirm('Reset all segments to default settings?')) {
                // Reset to original discount tiers
                wheelOptions = [
                    { text: "10K Discount", color: "#667eea", textRotation: 60 },
                    { text: "15K Discount", color: "#f97316", textRotation: 60 },
                    { text: "20K Discount", color: "#eab308", textRotation: 60 },
                    { text: "50K Discount", color: "#22c55e", textRotation: 60 },
                    { text: "1 Lakh Discount", color: "#3b82f6", textRotation: 60 },
                    { text: "Free IVF", color: "#8b5cf6", textRotation: 60 }
                ];
                generateSegmentInputs();
                createWheel();
            }
        }
        
        function randomizeColors() {
            const colors = ["#ef4444", "#f97316", "#eab308", "#22c55e", "#3b82f6", "#8b5cf6", "#ec4899", "#06b6d4", "#84cc16", "#f59e0b"];
            wheelOptions.forEach(option => {
                option.color = colors[Math.floor(Math.random() * colors.length)];
            });
            generateSegmentInputs();
            createWheel();
        }
        
        function applyGlobalRotation() {
            const globalRotation = document.getElementById('globalRotation').value;
            const rotation = parseInt(globalRotation) || 60;
            
            wheelOptions.forEach(option => {
                option.textRotation = rotation;
            });
            
            generateSegmentInputs();
            createWheel();
            alert(`Applied ${rotation}° rotation to all segments!`);
        }
        
        function applyGlobalTextSize() {
            const textSize = document.getElementById('globalTextSize').value;
            
            // Update CSS for segment text size
            const style = document.createElement('style');
            style.textContent = `
                .wheel-segment {
                    font-size: ${textSize} !important;
                }
            `;
            document.head.appendChild(style);
            
            createWheel();
            alert(`Applied ${textSize} text size to all segments!`);
        }
        
        function applyPreset(presetName) {
            switch(presetName) {
                case 'rainbow':
                    wheelOptions.forEach((option, index) => {
                        const rainbowColors = ["#ef4444", "#f97316", "#eab308", "#22c55e", "#3b82f6", "#8b5cf6"];
                        option.color = rainbowColors[index % rainbowColors.length];
                        option.textRotation = 45;
                    });
                    break;
                    
                case 'business':
                    wheelOptions.forEach(option => {
                        const businessColors = ["#1f2937", "#374151", "#4b5563", "#6b7280", "#374151", "#1f2937"];
                        option.color = businessColors[Math.floor(Math.random() * businessColors.length)];
                        option.textRotation = 90;
                    });
                    break;
                    
                case 'medical':
                    wheelOptions.forEach(option => {
                        const medicalColors = ["#059669", "#10b981", "#34d399", "#0891b2", "#0284c7", "#3b82f6"];
                        option.color = medicalColors[Math.floor(Math.random() * medicalColors.length)];
                        option.textRotation = 60;
                    });
                    break;
                    
                case 'party':
                    wheelOptions.forEach(option => {
                        const partyColors = ["#ec4899", "#f59e0b", "#eab308", "#84cc16", "#06b6d4", "#8b5cf6"];
                        option.color = partyColors[Math.floor(Math.random() * partyColors.length)];
                        option.textRotation = 30;
                    });
                    break;
            }
            
            generateSegmentInputs();
            createWheel();
            alert(`Applied ${presetName} preset!`);
        }
        
        // Phone number login functionality
        function showPhoneLoginModal() {
            document.getElementById('phoneLoginModal').classList.remove('hidden');
        }
        
        function hidePhoneLoginModal() {
            document.getElementById('phoneLoginModal').classList.add('hidden');
        }

        function showOtpModal() {
            document.getElementById('otpModal').classList.remove('hidden');
        }

        function hideOtpModal() {
            document.getElementById('otpModal').classList.add('hidden');
        }

        function showSmsErrorModal(errorMessage) {
            document.getElementById('smsErrorMessage').textContent = errorMessage;
            document.getElementById('smsErrorModal').classList.remove('hidden');
        }

        function hideSmsErrorModal() {
            document.getElementById('smsErrorModal').classList.add('hidden');
        }

        function showExistingUserResult(previousResult, previousCouponCode) {
            // Set up the wheel to show the user has already played
            canSpin = false;
            
            // Hide the entire wheel container
            const wheelContainer = document.querySelector('.wheel-container');
            if (wheelContainer) {
                wheelContainer.style.display = 'none';
            }
            
            // Show the result card with their previous win
            const resultTextMapping = {
                "Vdiscover @499": "Vdiscover 5 Step Analysis & Consultation @ Rs <s>900</s> 499",
                "Free Vdiscover": "FREE Vdiscover 5 Step Analysis & Consultation",
                "Extra 10% off": "Additional 10% off on bill - Enjoy total discounts up to 40%!",
                "Extra 15% off": "Additional 15% off on bill - Enjoy total discounts up to 45%!",
                "Extra 20% off": "Additional 20% off on bill - Enjoy total discounts up to 50%!"
            };

            const resultText = resultTextMapping[previousResult] || previousResult;
            
            // Update result display
            document.getElementById('resultRecordedId').textContent = recordedId;
            document.getElementById('resultText').innerHTML = resultText;
            document.getElementById('couponCode').textContent = previousCouponCode;
            
            // Show result with animation
            const result = document.getElementById('result');
            result.classList.remove('hidden');
            setTimeout(() => {
                result.classList.remove('scale-95', 'opacity-0');
                result.classList.add('scale-100', 'opacity-100');
            }, 50);
            
            // Hide the spin status section (we only want the main result card)
            const spinStatusEl = document.getElementById('spinStatus');
            if (spinStatusEl) spinStatusEl.classList.add('hidden');
        }
        
        
        
        // Show error message to user
        function showErrorMessage(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'w-full max-w-sm';
            errorDiv.innerHTML = `
                <div class="glass-card p-6 text-center">
                    <div class="text-4xl mb-4">⚠️</div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Unable to Load</h3>
                    <p class="text-gray-600 mb-4">${message}</p>
                    <button onclick="location.reload()" class="add-button">
                        Try Again
                    </button>
                </div>
            `;
            
            // Replace wheel container with error message
            const wheelContainer = document.querySelector('.glass-card');
            if (wheelContainer) {
                wheelContainer.parentNode.replaceChild(errorDiv, wheelContainer);
            }
        }
        
        function validatePhoneNumber(phone) {
            // Remove any non-digit characters
            const cleanPhone = phone.replace(/\D/g, '');
            // Check if it's exactly 10 digits
            return cleanPhone.length === 10 && /^\d{10}$/.test(cleanPhone);
        }

        async function sendOtp(phoneNumber, userName) {
            const button = document.querySelector('#phoneLoginForm button');
            const errorDiv = document.getElementById('phoneError');
            errorDiv.textContent = ''; // Clear previous errors
            button.disabled = true;
            button.textContent = 'Sending OTP...';

            const formData = new FormData();
            formData.append('action', 'ajax_contact_form_mobile_otp');
            formData.append('mobile_number', phoneNumber);
            formData.append('user_name', userName);

            try {
                const response = await fetch('api/otp.php', {
                    method: 'POST',
                    body: formData
                });

                const resultText = await response.text();
                const result = JSON.parse(resultText);

                if (result.type === 'success') {
                    // Store phone number and name temporarily
                    sessionStorage.setItem('tempPhoneNumber', phoneNumber);
                    sessionStorage.setItem('tempUserName', userName);
                    hidePhoneLoginModal();
                    showOtpModal();
                } else if (result.type === 'existing_user') {
                    // User already participated, show their winning card
                    const cleanPhoneNumber = phoneNumber.replace(/\D/g, '');
                    recordedId = cleanPhoneNumber;
                    hidePhoneLoginModal();
                    showExistingUserResult(result.previous_result, result.previous_coupon_code);
                } else {
                    errorDiv.textContent = result.message;
                }
            } catch (error) {
                console.error('Error sending OTP:', error);
                errorDiv.textContent = 'An error occurred. Please try again.';
            } finally {
                button.disabled = false;
                button.textContent = 'Continue to Spin Wheel';
            }
        }

        async function verifyOtp(phoneNumber, otp) {
            const button = document.querySelector('#otpVerifyForm button');
            button.disabled = true;
            button.textContent = 'Verifying...';

            const formData = new FormData();
            formData.append('action', 'ajax_contact_form_mobile_verified_otp');
            formData.append('mobile_number', phoneNumber);
            formData.append('mobile_otp', otp);

            try {
                const response = await fetch('api/otp.php', {
                    method: 'POST',
                    body: formData
                });

                const resultText = await response.text();
                const result = JSON.parse(resultText);

                if (result.type === 'success') {
                    // OTP Verified
                    const cleanPhoneNumber = phoneNumber.replace(/\D/g, '');
                    recordedId = cleanPhoneNumber;
                    sessionStorage.removeItem('tempPhoneNumber');
                    sessionStorage.removeItem('tempUserName');
                    
                    console.log('Phone number verified and set as recordedId:', cleanPhoneNumber);
                    
                    hideOtpModal();
                    loadWheelData();
                } else {
                    alert('OTP Verification Failed: ' + result.message);
                }
            } catch (error) {
                console.error('Error verifying OTP:', error);
                alert('An error occurred during OTP verification. Please try again.');
            } finally {
                button.disabled = false;
                button.textContent = 'Verify & Spin';
            }
        }
        
        // Handle phone login form submission
        document.getElementById('phoneLoginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const nameInput = document.getElementById('nameInput');
            const phoneInput = document.getElementById('phoneInput');
            const userName = nameInput.value.trim();
            const phoneNumber = phoneInput.value.trim();
            const dummyNumber = '9999999999'; // The dummy number

            if (!userName || userName.length < 2) {
                alert('Please enter your full name');
                nameInput.focus();
                return;
            }

            if (!validatePhoneNumber(phoneNumber)) {
                alert('Please enter a valid 10-digit phone number');
                phoneInput.focus();
                return;
            }

            if (phoneNumber === dummyNumber) {
                // It's the dummy number, bypass OTP
                console.log('Dummy number entered, bypassing OTP.');
                recordedId = phoneNumber;
                // Store the name for dummy number users too
                sessionStorage.setItem('tempUserName', userName);
                hidePhoneLoginModal();
                loadWheelData();
            } else {
                // Check if user exists first, then either send OTP or show existing result
                sendOtp(phoneNumber, userName);
            }
        });

        // Clear error message on input
        document.getElementById('nameInput').addEventListener('input', function() {
            document.getElementById('phoneError').textContent = '';
        });
        
        document.getElementById('phoneInput').addEventListener('input', function() {
            document.getElementById('phoneError').textContent = '';
        });

        // Handle OTP verification form submission
        document.getElementById('otpVerifyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const otpInput = document.getElementById('otpInput');
            const otp = otpInput.value.trim();
            const phoneNumber = sessionStorage.getItem('tempPhoneNumber');

            if (!/^\d{4}$/.test(otp)) {
                alert('Please enter a valid 4-digit OTP.');
                otpInput.focus();
                return;
            }

            if (!phoneNumber) {
                alert('Phone number not found. Please start over.');
                hideOtpModal();
                showPhoneLoginModal();
                return;
            }

            verifyOtp(phoneNumber, otp);
        });
        
        // Check if user needs to enter phone number
        function checkPhoneLogin() {
            if (!recordedId) {
                showPhoneLoginModal();
                return false;
            }
            return true;
        }
        
        // Initialize application
        function initializeApp() {
            if (recordedId) {
                // User has a recorded ID, load wheel data
                loadWheelData();
            } else {
                // No recorded ID, show phone login modal
                showPhoneLoginModal();
            }
            
            // Debug: Log current session storage
            console.log('Current sessionStorage tempUserName:', sessionStorage.getItem('tempUserName'));
            console.log('Current recordedId:', recordedId);
            
            // Generate segment inputs after wheel data is loaded
            setTimeout(() => {
                generateSegmentInputs();
            }, 1000);
            
            // Display recordedId if present
            if (recordedId) {
                console.log('Recorded ID:', recordedId);
            }
        }
        
        // Start the application
        initializeApp();
    </script>
</body>
</html>