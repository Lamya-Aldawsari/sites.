import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function Cart() {
    const [cart, setCart] = useState(null);
    const [loading, setLoading] = useState(true);
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
        } finally {
            setLoading(false);
        }
    };

    const updateQuantity = async (itemId, newQuantity) => {
        try {
            const token = localStorage.getItem('token');
            await axios.put(
                `/api/cart/items/${itemId}`,
                { quantity: newQuantity },
                {
                    headers: { Authorization: `Bearer ${token}` },
                }
            );
            fetchCart();
        } catch (error) {
            alert('Failed to update quantity');
        }
    };

    const removeItem = async (itemId) => {
        try {
            const token = localStorage.getItem('token');
            await axios.delete(`/api/cart/items/${itemId}`, {
                headers: { Authorization: `Bearer ${token}` },
            });
            fetchCart();
        } catch (error) {
            alert('Failed to remove item');
        }
    };

    const handleCheckout = () => {
        navigate('/checkout');
    };

    if (loading) {
        return <div className="text-center py-12">Loading cart...</div>;
    }

    if (!cart || !cart.items || cart.items.length === 0) {
        return (
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h1 className="text-3xl font-bold text-gray-900 mb-8">Shopping Cart</h1>
                <div className="text-center py-12">
                    <p className="text-gray-500 mb-4">Your cart is empty</p>
                    <button
                        onClick={() => navigate('/equipment')}
                        className="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700"
                    >
                        Browse Equipment
                    </button>
                </div>
            </div>
        );
    }

    const total = cart.items.reduce((sum, item) => sum + item.quantity * item.price, 0);

    return (
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8">
            <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-4 sm:mb-6 md:mb-8">Shopping Cart</h1>
            
            <div className="bg-white rounded-lg shadow-md p-4 sm:p-6">
                {cart.items.map((item) => (
                    <div key={item.id} className="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b pb-4 mb-4 gap-3 sm:gap-0">
                        <div className="flex-1">
                            <h3 className="text-base sm:text-lg font-semibold">{item.equipment?.name}</h3>
                            <p className="text-xs sm:text-sm text-gray-600">${item.price} each</p>
                        </div>
                        <div className="flex items-center justify-between sm:justify-end sm:space-x-4">
                            <div className="flex items-center space-x-2">
                                <button
                                    onClick={() => updateQuantity(item.id, item.quantity - 1)}
                                    className="px-2 sm:px-3 py-1 border rounded text-sm sm:text-base"
                                    disabled={item.quantity <= 1}
                                >
                                    -
                                </button>
                                <span className="text-sm sm:text-base">{item.quantity}</span>
                                <button
                                    onClick={() => updateQuantity(item.id, item.quantity + 1)}
                                    className="px-2 sm:px-3 py-1 border rounded text-sm sm:text-base"
                                >
                                    +
                                </button>
                            </div>
                            <p className="text-base sm:text-lg font-semibold">${(item.quantity * item.price).toFixed(2)}</p>
                            <button
                                onClick={() => removeItem(item.id)}
                                className="text-red-600 hover:text-red-800 text-sm sm:text-base"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                ))}
                
                <div className="mt-4 sm:mt-6 pt-4 border-t">
                    <div className="flex justify-between items-center mb-4">
                        <span className="text-lg sm:text-xl font-bold">Total:</span>
                        <span className="text-lg sm:text-xl font-bold">${total.toFixed(2)}</span>
                    </div>
                    <button
                        onClick={handleCheckout}
                        className="w-full bg-blue-600 text-white py-2 sm:py-3 px-4 rounded-md hover:bg-blue-700 text-sm sm:text-base"
                    >
                        Proceed to Checkout
                    </button>
                </div>
            </div>
        </div>
    );
}

