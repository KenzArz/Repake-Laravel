{{--
    Theme Example Component
    
    This component demonstrates how to use the converted theme
    in Laravel Blade templates.
--}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Example</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/theme/theme.css') }}">
    
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .card {
            padding: 1.5rem;
            border-width: 1px;
            border-style: solid;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-family: var(--font-heading);
        }
        
        .color-swatch {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            border: 1px solid var(--color-border-default);
            margin-right: 1rem;
        }
        
        .flex {
            display: flex;
            align-items: center;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Typography Examples -->
        <section>
            <h1 class="text-display">Theme Example</h1>
            <p class="text-body text-secondary" style="margin-top: 0.5rem;">
                Demonstrating the converted theme from Dart to Laravel
            </p>
        </section>

        <!-- Typography Scale -->
        <section style="margin-top: 3rem;">
            <h2 class="text-h2">Typography Scale</h2>
            
            <div class="card surface-elevated border-default">
                <h1 class="text-display">Display Text (40px)</h1>
                <h1>Heading 1 (32px)</h1>
                <h2>Heading 2 (28px)</h2>
                <h3>Heading 3 (24px)</h3>
                <h4>Heading 4 (20px)</h4>
                <h5>Heading 5 (18px)</h5>
                <p class="text-body-large">Body Large Text (17px)</p>
                <p class="text-body">Regular Body Text (16px)</p>
                <p class="text-label">Label Text (14px)</p>
                <p class="text-small-label">Small Label Text (12px)</p>
                <p class="text-caption">Caption Text (12px)</p>
            </div>
        </section>

        <!-- Color Palette -->
        <section style="margin-top: 3rem;">
            <h2 class="text-h2">Color Palette</h2>
            
            <!-- Base Colors -->
            <div class="card surface-elevated border-default">
                <h3 class="text-h4">Base Colors</h3>
                <div class="grid">
                    <div>
                        <div class="flex">
                            <div class="color-swatch" style="background-color: var(--color-lime);"></div>
                            <div>
                                <p class="text-label">Lime</p>
                                <p class="text-caption text-muted">#B8D94E</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="flex">
                            <div class="color-swatch" style="background-color: var(--color-charcoal);"></div>
                            <div>
                                <p class="text-label">Charcoal</p>
                                <p class="text-caption text-muted">#202320</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="flex">
                            <div class="color-swatch" style="background-color: var(--color-off-white);"></div>
                            <div>
                                <p class="text-label">Off White</p>
                                <p class="text-caption text-muted">#F5F5F0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Neutral Scale -->
            <div class="card surface-elevated border-default">
                <h3 class="text-h4">Neutral Scale</h3>
                <div class="grid">
                    @foreach([50, 100, 200, 300, 400, 500, 600, 700, 800, 900] as $shade)
                    <div>
                        <div class="flex">
                            <div class="color-swatch" style="background-color: var(--color-neutral-{{ $shade }});"></div>
                            <div>
                                <p class="text-label">Neutral {{ $shade }}</p>
                                <p class="text-caption text-muted">
                                    @php echo \App\Helpers\ThemeHelper::neutral($shade); @endphp
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Semantic Colors -->
        <section style="margin-top: 3rem;">
            <h2 class="text-h2">Semantic Colors</h2>
            
            <div class="card surface-elevated border-default">
                <h3 class="text-h4">Status Badges</h3>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
                    <span class="badge badge-success">Success</span>
                    <span class="badge badge-warning">Warning</span>
                    <span class="badge badge-error">Error</span>
                    <span class="badge badge-info">Info</span>
                </div>
            </div>

            <div class="card surface-elevated border-default">
                <h3 class="text-h4">Alerts</h3>
                
                <div class="badge-success" style="padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <strong class="text-label">Success!</strong>
                    <p class="text-body" style="margin-top: 0.25rem;">Your operation completed successfully.</p>
                </div>

                <div class="badge-warning" style="padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <strong class="text-label">Warning!</strong>
                    <p class="text-body" style="margin-top: 0.25rem;">Please review your information before proceeding.</p>
                </div>

                <div class="badge-error" style="padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <strong class="text-label">Error!</strong>
                    <p class="text-body" style="margin-top: 0.25rem;">Something went wrong. Please try again.</p>
                </div>

                <div class="badge-info" style="padding: 1rem; border-radius: 8px;">
                    <strong class="text-label">Info</strong>
                    <p class="text-body" style="margin-top: 0.25rem;">Here's some helpful information for you.</p>
                </div>
            </div>
        </section>

        <!-- Buttons -->
        <section style="margin-top: 3rem;">
            <h2 class="text-h2">Buttons</h2>
            
            <div class="card surface-elevated border-default">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button class="button bg-primary">Primary Button</button>
                    <button class="button bg-secondary">Secondary Button</button>
                    <button class="button bg-success">Success Button</button>
                    <button class="button bg-error">Error Button</button>
                </div>
            </div>
        </section>

        <!-- Form Example -->
        <section style="margin-top: 3rem; margin-bottom: 3rem;">
            <h2 class="text-h2">Form Example</h2>
            
            <div class="card surface-elevated border-default">
                <form>
                    <div style="margin-bottom: 1rem;">
                        <label class="text-label" style="display: block; margin-bottom: 0.5rem;">
                            Email Address
                        </label>
                        <input 
                            type="email" 
                            class="border-default" 
                            placeholder="your@email.com"
                            style="width: 100%; padding: 0.75rem; border-width: 1px; border-radius: 6px; font-family: var(--font-body);"
                        >
                        <span class="text-caption text-muted" style="display: block; margin-top: 0.25rem;">
                            We'll never share your email with anyone else.
                        </span>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label class="text-label" style="display: block; margin-bottom: 0.5rem;">
                            Password
                        </label>
                        <input 
                            type="password" 
                            class="border-default" 
                            placeholder="Enter your password"
                            style="width: 100%; padding: 0.75rem; border-width: 1px; border-radius: 6px; font-family: var(--font-body);"
                        >
                    </div>

                    <button type="submit" class="button bg-primary">
                        Submit Form
                    </button>
                </form>
            </div>
        </section>
    </div>
</body>
</html>
