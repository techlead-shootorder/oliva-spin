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
            width: 280px;
            height: 280px;
            margin: 0 auto;
        }
        
        @media (min-width: 640px) {
            .wheel-container {
                width: 340px;
                height: 340px;
            }
        }
        
        @media (min-width: 768px) {
            .wheel-container {
                width: 400px;
                height: 400px;
            }
        }
        
        .wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            position: relative;
            overflow: hidden;
            transition: transform 4s cubic-bezier(0.17, 0.67, 0.12, 0.99);
            box-shadow: 0 12px 40px rgba(102, 126, 234, 0.3), 0 0 0 4px rgba(102, 126, 234, 0.2), 0 0 0 6px rgba(102, 126, 234, 0.1);
            border: 2px solid rgba(102, 126, 234, 0.4);
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
        
        @media (min-width: 640px) {
            .spin-button {
                width: 90px;
                height: 90px;
                font-size: 13px;
            }
        }
        
        @media (min-width: 768px) {
            .spin-button {
                width: 100px;
                height: 100px;
                font-size: 14px;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
    <div class="min-h-screen flex flex-col items-center justify-center p-4 space-y-6">
         
        <!-- Phone Number Login Modal -->
        <div id="phoneLoginModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
            <div class="glass-card p-6 w-full max-w-sm">
                <div class="text-center mb-6">
                    <div class="text-4xl mb-3">📱</div>
                    <h2 class="text-xl font-bold text-gray-800 mb-2">Enter Your Phone Number</h2>
                    <p class="text-gray-600 text-sm">Please enter your phone number to continue</p>
                </div>
                
                <form id="phoneLoginForm" class="space-y-4">
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
                    
                    <button type="submit" class="add-button w-full">
                        Continue to Spin Wheel
                    </button>
                </form>
            </div>
        </div>

        <!-- Header -->
        <div class="text-center mb-2">
            <img src="https://oasisindia.in/_next/image/?url=https%3A%2F%2Fimages.oasisindia.in%2Fwebsite%2Flogo%2Flogo2.png&w=640&q=75" 
                 alt="Oasis India Logo" 
                 class="mx-auto mb-4 h-12 sm:h-16 md:h-20 w-auto">
            <h1 id="wheelTitle" class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800 mb-2">
                Oasis Spin Wheel's
            </h1>
         
            <p id="wheelDescription" class="text-gray-600 text-sm sm:text-base">
                Spin once and win amazing discounts on IVF treatments!
            </p>
        </div>
        
        <!-- Wheel Container -->
        <div class="glass-card p-4 sm:p-6 md:p-8">
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
                    <span id="spinText">🎲<br>SPIN</span>
                </button>
            </div>
        </div>
        
        <!-- Result -->
        <div id="result" class="result-card hidden w-full max-w-sm transform transition-all duration-500 scale-95 opacity-0">
            <p class="text-sm font-medium text-white/80 mb-2">Registered Phone: <span id="resultRecordedId" class="font-bold"></span></p>
            
            <h3 class="text-xl font-bold mb-3 relative z-10">Congratulations!</h3>
            <p class="text-2xl font-bold mb-4 relative z-10" id="resultText"></p>
            <div class="mt-4 p-5 bg-white/25 rounded-xl border border-white/30 backdrop-blur-sm relative z-10">
                <p class="text-sm font-semibold mb-2 text-white/90">🎫 Your Coupon Code:</p>
                <p class="text-xl font-mono font-bold tracking-wider text-white" id="couponCode"></p>
            </div>
            <div class="mt-4 text-xs text-white/90 font-medium space-y-1">
                <p>Show the code at the billing counter to avail the discount.</p>
                <p class="font-bold">Do not refresh this page.</p>
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
                        <div class="mt-4 p-4 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl border-2 border-emerald-200 shadow-sm">
                            <p class="text-sm text-emerald-700 font-semibold mb-2">🎫 Your Coupon Code:</p>
                            <p class="text-lg font-mono font-bold text-emerald-800 tracking-wider" id="previousCouponCode"></p>
                        </div>
                        <p class="text-sm text-indigo-600 mt-3 font-medium">🎉 Thank you for participating!</p>
                    </div>
                </div>
            </div>
        </div>
        
        
        <!-- Segment Management -->
     
        
        <!-- Privacy Policy Modal -->
        <div id="privacyModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
            <div class="glass-card p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Terms & Conditions – Wheel of Hope</h2>
                    <button onclick="hidePrivacyModal()" class="text-gray-500 hover:text-gray-700 text-xl">✕</button>
                </div>
                
                <div class="text-sm text-gray-700 space-y-3">
                    <p>1. The "Wheel of Hope" Contest is organized and operated by Oasis Fertility ("Organizer", "We", "Us").</p>
                    <p>2. The Contest is open only to eligible married couples who comply with ART (Assisted Reproductive Technology) Regulations.</p>
                    <p>3. This offer is exclusively available to patients who have been evaluated and allocated an IVF cycle at any Oasis Fertility centre after payment of the minimum advance.</p>
                    <p>4. The Wheel of Hope activity will be conducted in person at the clinic on the day of IVF cycle allocation.</p>
                    <p>5. Only one spin per patient is permitted under this Contest.</p>
                    <p>6. A unique code will be generated upon spinning the wheel, which must be shared by the participant with the centre team during the same-day billing process.</p>
                    <p>7. All rewards will be applied as immediate, same-day discounts on the billing for the IVF cycle. Rewards cannot be applied retroactively or deferred to another day.</p>
                    <p>8. The participant will not be eligible for any other discounts or promotional offers throughout the IVF cycle or associated treatments once the Wheel of Hope benefit is claimed.</p>
                    <p>9. If the billing amount is less than the reward value, the remaining balance is forfeited — no cash refund or carryover will be provided.</p>
                    <p>10. Rewards are non-encashable, non-transferable, and valid only for the patient to whom the reward was issued.</p>
                    <p>11. The "Free IVF" reward includes one standard IVF cycle only, and specifically excludes:</p>
                    <ul class="ml-6 list-disc">
                        <li>Medicines and pharmacy bills</li>
                        <li>Embryo/sperm freezing charges</li>
                        <li>Donor costs</li>
                        <li>Advanced laboratory procedures, etc;</li>
                    </ul>
                    <p>12. Discounts apply only to services billed directly by Oasis Fertility and cannot be used for:</p>
                    <ul class="ml-6 list-disc">
                        <li>Diagnostic labs or pharmacy invoices</li>
                        <li>Third-party services or outsourced procedures</li>
                        <li>Add-ons like genetic screening, donor cycles, embryo freezing, or surrogacy, unless explicitly stated otherwise</li>
                    </ul>
                    <p>13. In the event a patient is medically disqualified post-allocation, the reward will automatically stand void.</p>
                    <p>14. Oasis Fertility reserves the right to verify identity and medical eligibility prior to applying any discount.</p>
                    <p>15. Any misuse, misrepresentation, or attempt to manipulate the process (including multiple entries, proxy spins, or false identities) will result in immediate disqualification and reward cancellation.</p>
                    <p>16. The reward must be redeemed on the same day as the spin. No extensions will be allowed.</p>
                    <p>17. Oasis Fertility shall not be liable for delays or cancellations of the campaign due to force majeure events such as strikes, natural disasters, pandemics, or technical disruptions.</p>
                    <p>18. Participation in this Contest does not guarantee any clinical or medical outcome, and all treatments will proceed as per standard medical protocols and informed consent.</p>
                    <p>19. The campaign will run from [Insert Start Date] to [Insert End Date], unless extended or withdrawn at the sole discretion of the Organizer.</p>
                    <p>20. By participating, the patient consents to the limited use of their first name, city, or anonymized testimonials for marketing and promotional purposes.</p>
                    <p>21. Participants will be governed by the Privacy Policy available at <a href="https://www.oasisindia.in" target="_blank" class="text-blue-600 hover:underline">www.oasisindia.in</a>.</p>
                    <p>22. All decisions made by the clinic's management and medical team regarding Contest participation and reward validity will be final and binding.</p>
                    <p>23. Any disputes arising out of or related to this Contest will be subject to the exclusive jurisdiction of the courts in Hyderabad, Telangana.</p>
                </div>
                
                <div class="mt-6 text-center">
                    <button onclick="hidePrivacyModal()" class="add-button">
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs text-gray-500 mt-8">
            <p>Tap to spin | <button onclick="showPrivacyModal()" class="text-blue-600 hover:underline cursor-pointer">Privacy Policy</button></p>
        </div>
    </div>

    <script>
        // Get URL parameters
        function getUrlParameter(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }
        
        // Get recorded ID from URL or session storage
        let recordedId = getUrlParameter('recordedId') || sessionStorage.getItem('phoneNumber');
        
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
                    
                    // Update UI elements
                    document.getElementById('wheelTitle').textContent = data.settings.wheel_title || '🎯 Oasis Spin Wheel';
                    document.getElementById('wheelDescription').textContent = data.settings.wheel_description || 'Spin once and win amazing discounts on IVF treatments!';
                    
                    // Update current week if element exists
                    const currentWeekElement = document.getElementById('currentWeek');
                    if (currentWeekElement) {
                        currentWeekElement.textContent = currentWeek;
                    }
                    
                    // Show previous result ONLY if user already spun AND has previous result
                    if (!canSpin && data.previousResult) {
                        document.getElementById('previousResult').textContent = data.previousResult;
                        document.getElementById('statusRecordedId').textContent = recordedId;
                        
                        // Show coupon code if available
                        if (data.previousCouponCode) {
                            document.getElementById('previousCouponCode').textContent = data.previousCouponCode;
                        } else {
                            document.getElementById('previousCouponCode').textContent = 'Code not available';
                        }
                        
                        document.getElementById('spinStatus').classList.remove('hidden');
                        document.getElementById('spinBtn').disabled = true;
                        document.getElementById('spinText').innerHTML = '✓<br>USED';
                    } else if (canSpin) {
                        // User can spin - make sure button is enabled
                        document.getElementById('spinBtn').disabled = false;
                        document.getElementById('spinText').innerHTML = '🎲<br>SPIN';
                    }
                    
                    createWheel();
                } else {
                    console.error('Failed to load wheel data:', data.error);
                    // Use fallback data for testing
                    loadFallbackData();
                }
            } catch (error) {
                console.error('Error loading wheel data:', error);
                // Use fallback data when API is not available
                loadFallbackData();
            }
        }
        
        // Fallback data when API is not available
        function loadFallbackData() {
            console.log('🔄 Loading fallback data for testing...');
            wheelOptions = [
                { text: "10K Discount", color: "#ef4444", probability: 20, code: "SAVE10K" },
                { text: "15K Discount", color: "#f97316", probability: 18, code: "SAVE15K" },
                { text: "20K Discount", color: "#eab308", probability: 16, code: "SAVE20K" },
                { text: "50K Discount", color: "#22c55e", probability: 14, code: "SAVE50K" },
                { text: "1 Lakh Discount", color: "#3b82f6", probability: 12, code: "SAVE100K" },
                { text: "Free IVF", color: "#8b5cf6", probability: 10, code: "FREEIVF" }
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
                    const response = await fetch('api/spin.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            recordedId: recordedId
                        })
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

                    // --- NEW ROTATION LOGIC ---

                    // 1. Define the mapping from API result text to a simple label
                    const apiToLabelMap = {
                        "10K Discount": "10K",
                        "15K Discount": "15K",
                        "20K Discount": "20K",
                        "50K Discount": "50K",
                        "1 Lakh Discount": "1L",
                        "Free IVF": "Free IVF"
                    };

                    // 2. Define the degree ranges for each label from the user's table
                    const degreeRanges = {
                        "1L":       [{ min: 0, max: 60 }, { min: 360, max: 420 }, { min: 720, max: 780 }, { min: 1080, max: 1140 }, { min: 1440, max: 1500 }, { min: 1800, max: 1860 }, { min: 2160, max: 2220 }, { min: 2520, max: 2580 }, { min: 2880, max: 2940 }, { min: 3240, max: 3300 }],
                        "Free IVF": [{ min: 60, max: 120 }, { min: 420, max: 480 }, { min: 780, max: 840 }, { min: 1140, max: 1200 }, { min: 1500, max: 1560 }, { min: 1860, max: 1920 }, { min: 2220, max: 2280 }, { min: 2580, max: 2640 }, { min: 2940, max: 3000 }, { min: 3300, max: 3360 }],
                        "50K":      [{ min: 120, max: 180 }, { min: 480, max: 540 }, { min: 840, max: 900 }, { min: 1200, max: 1260 }, { min: 1560, max: 1620 }, { min: 1920, max: 1980 }, { min: 2280, max: 2340 }, { min: 2640, max: 2700 }, { min: 3000, max: 3060 }, { min: 3360, max: 3420 }],
                        "20K":      [{ min: 180, max: 240 }, { min: 540, max: 600 }, { min: 900, max: 960 }, { min: 1260, max: 1320 }, { min: 1620, max: 1680 }, { min: 1980, max: 2040 }, { min: 2340, max: 2400 }, { min: 2700, max: 2760 }, { min: 3060, max: 3120 }, { min: 3420, max: 3480 }],
                        "15K":      [{ min: 240, max: 300 }, { min: 600, max: 660 }, { min: 960, max: 1020 }, { min: 1320, max: 1380 }, { min: 1680, max: 1740 }, { min: 2040, max: 2100 }, { min: 2400, max: 2460 }, { min: 2760, max: 2820 }, { min: 3120, max: 3180 }, { min: 3480, max: 3540 }],
                        "10K":      [{ min: 300, max: 360 }, { min: 660, max: 720 }, { min: 1020, max: 1080 }, { min: 1380, max: 1440 }, { min: 1740, max: 1800 }, { min: 2100, max: 2160 }, { min: 2460, max: 2520 }, { min: 2820, max: 2880 }, { min: 3180, max: 3240 }, { min: 3540, max: 3600 }]
                    };

                    // 3. Get the simple label for the winning result
                    const winnerLabel = apiToLabelMap[winner.text];

                    if (!winnerLabel || !degreeRanges[winnerLabel]) {
                        console.error("Could not find a matching label or degree range for winner:", winner.text);
                        // Fallback to a random spin if winner is not in the map
                        wheel.style.transform = `rotate(${360 * 5 + Math.random() * 360}deg)`;
                        return;
                    }

                    // 4. Get the possible degree ranges for the winning label
                    const possibleRanges = degreeRanges[winnerLabel];

                    // 5. Randomly select one of the possible ranges
                    const selectedRange = possibleRanges[Math.floor(Math.random() * possibleRanges.length)];

                    // 6. Pick a random degree value within the selected range
                    // To make it more visually appealing, we avoid the exact edges of the range.
                    const margin = 5; // 5 degrees margin from each edge
                    const randomAngle = Math.random() * (selectedRange.max - selectedRange.min - 2 * margin) + (selectedRange.min + margin);

                    const totalRotation = randomAngle;

                    console.log('🎯 New Rotation Debug:', {
                        winner: winner.text,
                        label: winnerLabel,
                        selectedRange: `${selectedRange.min}° - ${selectedRange.max}°`,
                        totalRotation: totalRotation.toFixed(1)
                    });

                    // --- END OF NEW ROTATION LOGIC ---
                    
                    // Apply rotation
                    wheel.style.transform = `rotate(${totalRotation}deg)`;

                    // Show result after animation
                    setTimeout(() => {
                        // Update result display
                        document.getElementById('resultRecordedId').textContent = recordedId;
                        document.getElementById('resultText').textContent = winner.text;
                        document.getElementById('couponCode').textContent = winner.code;
                        
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
        
        // Privacy Policy modal functionality
        function showPrivacyModal() {
            document.getElementById('privacyModal').classList.remove('hidden');
        }
        
        function hidePrivacyModal() {
            document.getElementById('privacyModal').classList.add('hidden');
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
        
        // Handle phone login form submission
        document.getElementById('phoneLoginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const phoneInput = document.getElementById('phoneInput');
            const phoneNumber = phoneInput.value.trim();
            
            if (!validatePhoneNumber(phoneNumber)) {
                alert('Please enter a valid 10-digit phone number');
                phoneInput.focus();
                return;
            }
            
            // Clean and store phone number
            const cleanPhoneNumber = phoneNumber.replace(/\D/g, '');
            recordedId = cleanPhoneNumber;
            sessionStorage.setItem('phoneNumber', cleanPhoneNumber);
            
            console.log('Phone number set as recordedId:', cleanPhoneNumber);
            
            // Hide modal and load wheel data
            hidePhoneLoginModal();
            
            // Load wheel data now that we have a recorded ID
            loadWheelData();
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