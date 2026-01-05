import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function Checkout() {
    const [cart, setCart] = useState(null);
    const [shipping, setShipping] = useState({
        address: '',
        city: '',
        state: '',
        country: '',
        zip: '',
    });
    const [loading, setLoading] = useState(false);
    const navigate = useNavigate();

    useEffect(() => {
        fetchCart();
    }, []);

    const fetchCart = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get('/api/cart', {
                headers: { Authorization: `Bearer ${token}` },
            });
            setCart(response.data);
        } catch (error) {
            console.error('Error fetching cart:', error);
            navigate('/cart');
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);

        try {
            const token = localStorage.getItem('token');
            const response = await axios.post(
                '/api/orders',
                shipping,
                {
                    headers: { Authorization: `Bearer ${token}` },
                }
            );

            // In a real app, you'd integrate Stripe Elements here
            // For now, we'll just show a success message
            alert('Order created! Payment integration needed.');
            navigate('/dashboard');
        } catch (error) {
            alert(error.response?.data?.message || 'Failed to create order');
        } finally {
            setLoading(false);
        }
    };

    if (!cart || !cart.items || cart.items.length === 0) {
        return (
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <p className="text-gray-500">Your cart is empty</p>
            </div>
        );
    }

    const subtotal = cart.items.reduce((sum, item) => sum + item.quantity * item.price, 0);
    const tax = subtotal * 0.10;
    const shippingCost = 25.00;
    const total = subtotal + tax + shippingCost;

    return (
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8">
            <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-4 sm:mb-6 md:mb-8">Checkout</h1>
            
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 md:gap-8">
                <div>
                    <h2 className="text-xl font-semibold mb-4">Shipping Information</h2>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Address</label>
                            <input
                                type="text"
                                required
                                value={shipping.address}
                                onChange={(e) => setShipping({ ...shipping, address: e.target.value })}
                                className="mt-1 block w-full border rounded-md px-3 py-2"
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">City</label>
                                <input
                                    type="text"
                                    required
                                    value={shipping.city}
                                    onChange={(e) => setShipping({ ...shipping, city: e.target.value })}
                                    className="mt-1 block w-full border rounded-md px-3 py-2"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">State</label>
                                <input
                                    type="text"
                                    required
                                    value={shipping.state}
                                    onChange={(e) => setShipping({ ...shipping, state: e.target.value })}
                                    className="mt-1 block w-full border rounded-md px-3 py-2"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Country</label>
                                <input
                                    type="text"
                                    required
                                    value={shipping.country}
                                    onChange={(e) => setShipping({ ...shipping, country: e.target.value })}
                                    className="mt-1 block w-full border rounded-md px-3 py-2"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">ZIP Code</label>
                                <input
                                    type="text"
                                    required
                                    value={shipping.zip}
                                    onChange={(e) => setShipping({ ...shipping, zip: e.target.value })}
                                    className="mt-1 block w-full border rounded-md px-3 py-2"
                                />
                            </div>
                        </div>
                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full bg-blue-600 text-white py-3 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50"
                        >
                            {loading ? 'Processing...' : 'Place Order'}
                        </button>
                    </form>
                </div>

                <div>
                    <h2 className="text-xl font-semibold mb-4">Order Summary</h2>
                    <div className="bg-white rounded-lg shadow-md p-6">
                        {cart.items.map((item) => (
                            <div key={item.id} className="flex justify-between mb-2">
                                <span>{item.equipment?.name} x{item.quantity}</span>
                                <span>${(item.quantity * item.price).toFixed(2)}</span>
                            </div>
                        ))}
                        <div className="border-t pt-4 mt-4 space-y-2">
                            <div className="flex justify-between">
                                <span>Subtotal</span>
                                <span>${subtotal.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Tax</span>
                                <span>${tax.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Shipping</span>
                                <span>${shippingCost.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between font-bold text-lg pt-2 border-t">
                                <span>Total</span>
                                <span>${total.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

