<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | SkyWings Airlines</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .hero-section {
            background: linear-gradient(135deg, #3B82F6 0%, #1E40AF 100%);
        }
        /* Tailwind CSS animations */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-down {
            animation: fadeInDown 1s ease-out forwards;
        }
        .animate-fade-in-up {
            animation: fadeInUp 1s ease-out 0.5s forwards;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <!-- Main Content -->
    <main>
        <!-- Hero Section with Integrated Header -->
        <section class="hero-section relative text-white h-96 flex flex-col">
            
            <!-- Header positioned over the hero image -->
            <header>
                <nav class="container mx-auto px-6 py-4 flex justify-center items-center">
                    <a href="#" class="text-3xl font-bold text-white" style="text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
                        ✈️ SkyWings Airlines
                    </a>
                </nav>
            </header>

            <!-- Centered Content -->
            <div class="flex-grow flex items-center justify-center">
                <div class="text-center">
                    <h1 class="text-5xl md:text-6xl font-extrabold mb-4 animate-fade-in-down">Connecting Your World</h1>
                    <p class="text-lg md:text-xl font-light animate-fade-in-up">Discover the story, mission, and passion behind SkyWings Airlines.</p>
                </div>
            </div>
        </section>

        <!-- About Us Section -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-6">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div class="rounded-lg overflow-hidden shadow-2xl">
                        <img src="https://placehold.co/600x400/EBF4FF/3B82F6?text=Our+Journey" alt="SkyWings Crew" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h2 class="text-4xl font-bold text-gray-800 mb-6">Welcome to SkyWings Airlines</h2>
                        <p class="text-gray-600 mb-4 leading-relaxed">
                            Founded with a vision to revolutionize air travel, SkyWings Airlines is dedicated to providing an unparalleled flying experience. Our mission is to connect people and places with the utmost commitment to safety, comfort, and reliability. We believe that a journey should be as memorable as the destination itself.
                        </p>
                        <p class="text-gray-600 leading-relaxed">
                            From our humble beginnings, we have grown into a trusted name in the aviation industry, thanks to our dedicated team and loyal passengers. We are constantly innovating to make your travels seamless, enjoyable, and extraordinary. Your sky is our home, and we are honored to share it with you.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our Services Section -->
        <section class="py-20 bg-gray-50">
            <div class="container mx-auto px-6 text-center">
                <h2 class="text-4xl font-bold mb-4">What We Offer</h2>
                <p class="text-gray-600 mb-12 max-w-2xl mx-auto">We provide a range of services designed to make your journey comfortable, entertaining, and delightful from start to finish.</p>
                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Service Card 1 -->
                    <div class="bg-white p-8 rounded-xl shadow-lg">
                        <div class="text-blue-600 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">In-Flight Entertainment</h3>
                        <p class="text-gray-600">Enjoy a wide selection of the latest movies, TV shows, music, and games on your personal screen.</p>
                    </div>
                    <!-- Service Card 2 -->
                    <div class="bg-white p-8 rounded-xl shadow-lg">
                         <div class="text-blue-600 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0c-.454-.303-.977-.454-1.5-.454V5.118a2.704 2.704 0 013 0 2.704 2.704 0 003 0 2.704 2.704 0 013 0 2.704 2.704 0 003 0V15.546z" />
                           </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Spacious Seating</h3>
                        <p class="text-gray-600">Relax in our ergonomically designed seats with extra legroom, ensuring a comfortable journey.</p>
                    </div>
                    <!-- Service Card 3 -->
                    <div class="bg-white p-8 rounded-xl shadow-lg">
                         <div class="text-blue-600 mb-4">
                           <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                           </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">World-Class Service</h3>
                        <p class="text-gray-600">Our friendly and professional cabin crew is always ready to assist you with a warm smile.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Contact Section -->
        <section class="py-20 bg-blue-600 text-white">
            <div class="container mx-auto px-6 text-center">
                <h2 class="text-4xl font-bold mb-4">Get in Touch</h2>
                <p class="mb-8 max-w-2xl mx-auto">For further information or if you are facing any problem, please do not hesitate to reach out to our customer support team. We are available 24/7 to assist you.</p>
                <div class="flex flex-col md:flex-row justify-center items-center space-y-4 md:space-y-0 md:space-x-8">
                    <div class="bg-white text-gray-800 rounded-lg p-6 shadow-lg">
                        <h3 class="font-semibold text-lg">Phone Number</h3>
                        <a href="tel:01717558653" class="text-blue-600 text-xl">01717-558653</a>
                    </div>
                    <div class="bg-white text-gray-800 rounded-lg p-6 shadow-lg">
                        <h3 class="font-semibold text-lg">Email Address</h3>
                        <a href="mailto:maksudmahianisa@gmail.com" class="text-blue-600 text-xl">maksudmahianisa@gmail.com</a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; 2025 SkyWings Airlines. All Rights Reserved.</p>
        </div>
    </footer>

</body>
</html>