import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function Verification() {
    const [documents, setDocuments] = useState([]);
    const [loading, setLoading] = useState(true);
    const [uploading, setUploading] = useState(false);

    useEffect(() => {
        fetchDocuments();
    }, []);

    const fetchDocuments = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get('/api/verification/documents', {
                headers: { Authorization: `Bearer ${token}` },
            });
            setDocuments(response.data);
        } catch (error) {
            console.error('Error fetching documents:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleUpload = async (e) => {
        e.preventDefault();
        setUploading(true);

        const formData = new FormData(e.target);

        try {
            const token = localStorage.getItem('token');
            await axios.post('/api/verification/documents', formData, {
                headers: {
                    Authorization: `Bearer ${token}`,
                    'Content-Type': 'multipart/form-data',
                },
            });
            alert('Document uploaded successfully!');
            fetchDocuments();
            e.target.reset();
        } catch (error) {
            alert(error.response?.data?.message || 'Failed to upload document');
        } finally {
            setUploading(false);
        }
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'approved':
                return 'bg-green-100 text-green-800';
            case 'rejected':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-yellow-100 text-yellow-800';
        }
    };

    return (
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8">
            <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-4 sm:mb-6 md:mb-8">Verification Documents</h1>

            <div className="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-6">
                <h2 className="text-lg sm:text-xl font-semibold mb-4">Upload Document</h2>
                <form onSubmit={handleUpload} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                        <select
                            name="document_type"
                            required
                            className="w-full border rounded-md px-3 py-2 text-sm sm:text-base"
                        >
                            <option value="">Select document type</option>
                            <option value="marine_license">Marine License</option>
                            <option value="boat_insurance">Boat Insurance</option>
                            <option value="commercial_registration">Commercial Registration</option>
                            <option value="captain_license">Captain License</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Document Number</label>
                        <input
                            type="text"
                            name="document_number"
                            className="w-full border rounded-md px-3 py-2 text-sm sm:text-base"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                        <input
                            type="date"
                            name="expiry_date"
                            className="w-full border rounded-md px-3 py-2 text-sm sm:text-base"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">File (PDF, JPG, PNG - Max 5MB)</label>
                        <input
                            type="file"
                            name="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            required
                            className="w-full border rounded-md px-3 py-2 text-sm sm:text-base"
                        />
                    </div>
                    <button
                        type="submit"
                        disabled={uploading}
                        className="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50 text-sm sm:text-base"
                    >
                        {uploading ? 'Uploading...' : 'Upload Document'}
                    </button>
                </form>
            </div>

            <div className="bg-white rounded-lg shadow-md p-4 sm:p-6">
                <h2 className="text-lg sm:text-xl font-semibold mb-4">Uploaded Documents</h2>
                {loading ? (
                    <p className="text-gray-500">Loading...</p>
                ) : documents.length === 0 ? (
                    <p className="text-gray-500">No documents uploaded yet</p>
                ) : (
                    <div className="space-y-4">
                        {documents.map((doc) => (
                            <div key={doc.id} className="border rounded-lg p-4">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <h3 className="font-semibold capitalize">{doc.document_type.replace('_', ' ')}</h3>
                                        {doc.document_number && (
                                            <p className="text-sm text-gray-600">Number: {doc.document_number}</p>
                                        )}
                                        {doc.expiry_date && (
                                            <p className="text-sm text-gray-600">Expires: {new Date(doc.expiry_date).toLocaleDateString()}</p>
                                        )}
                                    </div>
                                    <span className={`px-2 py-1 rounded text-xs sm:text-sm ${getStatusColor(doc.status)}`}>
                                        {doc.status}
                                    </span>
                                </div>
                                {doc.rejection_reason && (
                                    <p className="text-sm text-red-600 mt-2">Reason: {doc.rejection_reason}</p>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}

