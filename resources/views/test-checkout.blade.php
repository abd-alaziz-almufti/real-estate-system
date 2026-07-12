<!DOCTYPE html>
<html>
<head>
    <title>Test Stripe Checkout</title>
</head>
<body style="display: flex; justify-content: center; align-items: center; height: 100vh; background: #f3f4f6; font-family: sans-serif; margin: 0;">
    <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center;">
        <h2 style="margin-top: 0; color: #333;">Test Stripe Checkout</h2>
        <p style="color: #666; margin-bottom: 24px;">Click the button below to generate a Stripe Checkout Session for the "Basic" plan.</p>
        <form action="{{ route('test.checkout') }}" method="POST">
            @csrf
            <button type="submit" style="background: #6366f1; color: white; border: none; padding: 12px 24px; font-size: 16px; border-radius: 4px; cursor: pointer; transition: background 0.2s;">
                Checkout with Stripe
            </button>
        </form>
    </div>
</body>
</html>
