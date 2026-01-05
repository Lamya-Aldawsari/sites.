import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { format } from 'date-fns';

export default function MessagesView() {
    const [conversations, setConversations] = useState([]);
    const [selectedConversation, setSelectedConversation] = useState(null);
    const [messages, setMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchConversations();
    }, []);

    useEffect(() => {
        if (selectedConversation) {
            fetchMessages(selectedConversation.user.id);
        }
    }, [selectedConversation]);

    const fetchConversations = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get('/api/messages/conversations', {
                headers: { Authorization: `Bearer ${token}` },
            });
            setConversations(response.data);
        } catch (error) {
            console.error('Error fetching conversations:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchMessages = async (userId) => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get(`/api/messages/${userId}`, {
                headers: { Authorization: `Bearer ${token}` },
            });
            setMessages(response.data);
        } catch (error) {
            console.error('Error fetching messages:', error);
        }
    };

    const sendMessage = async (e) => {
        e.preventDefault();
        if (!newMessage.trim() || !selectedConversation) return;

        try {
            const token = localStorage.getItem('token');
            await axios.post(
                '/api/messages',
                {
                    receiver_id: selectedConversation.user.id,
                    message: newMessage,
                },
                {
                    headers: { Authorization: `Bearer ${token}` },
                }
            );
            setNewMessage('');
            fetchMessages(selectedConversation.user.id);
        } catch (error) {
            alert('Failed to send message');
        }
    };

    if (loading) {
        return <div className="text-center py-12">Loading messages...</div>;
    }

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Messages</h1>
                <p className="mt-2 text-gray-600">Communicate with customers and partners</p>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 h-[600px]">
                {/* Conversations List */}
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <div className="p-4 border-b">
                        <h3 className="font-semibold">Conversations</h3>
                    </div>
                    <div className="overflow-y-auto h-full">
                        {conversations.map((conv) => (
                            <div
                                key={conv.user.id}
                                onClick={() => setSelectedConversation(conv)}
                                className={`p-4 border-b cursor-pointer hover:bg-gray-50 ${
                                    selectedConversation?.user.id === conv.user.id ? 'bg-blue-50' : ''
                                }`}
                            >
                                <div className="flex justify-between items-start">
                                    <div className="flex-1">
                                        <p className="font-medium">{conv.user.name}</p>
                                        <p className="text-sm text-gray-600 truncate">
                                            {conv.last_message?.message}
                                        </p>
                                    </div>
                                    {conv.unread_count > 0 && (
                                        <span className="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">
                                            {conv.unread_count}
                                        </span>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Messages */}
                <div className="lg:col-span-2 bg-white rounded-lg shadow flex flex-col">
                    {selectedConversation ? (
                        <>
                            <div className="p-4 border-b">
                                <h3 className="font-semibold">{selectedConversation.user.name}</h3>
                            </div>
                            <div className="flex-1 overflow-y-auto p-4 space-y-4">
                                {messages.map((message) => {
                                    const isSent = message.sender_id === parseInt(localStorage.getItem('user_id') || '0');
                                    return (
                                        <div
                                            key={message.id}
                                            className={`flex ${isSent ? 'justify-end' : 'justify-start'}`}
                                        >
                                            <div
                                                className={`max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${
                                                    isSent
                                                        ? 'bg-blue-600 text-white'
                                                        : 'bg-gray-100 text-gray-900'
                                                }`}
                                            >
                                                <p>{message.message}</p>
                                                <p className={`text-xs mt-1 ${
                                                    isSent ? 'text-blue-100' : 'text-gray-500'
                                                }`}>
                                                    {format(new Date(message.created_at), 'hh:mm a')}
                                                </p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                            <form onSubmit={sendMessage} className="p-4 border-t">
                                <div className="flex space-x-2">
                                    <input
                                        type="text"
                                        value={newMessage}
                                        onChange={(e) => setNewMessage(e.target.value)}
                                        placeholder="Type a message..."
                                        className="flex-1 border rounded-md px-3 py-2 text-sm sm:text-base"
                                    />
                                    <button
                                        type="submit"
                                        className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm sm:text-base"
                                    >
                                        Send
                                    </button>
                                </div>
                            </form>
                        </>
                    ) : (
                        <div className="flex items-center justify-center h-full text-gray-500">
                            Select a conversation to start messaging
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

