// UPI QR Code Generator

class UPIQRGenerator {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        this.qrCode = null;
    }
    
    /**
     * Generate UPI QR Code
     * @param {string} upiId - NGO's UPI ID
     * @param {number} amount - Donation amount
     * @param {string} ngoName - NGO name
     */
    generate(upiId, amount, ngoName = 'NGO') {
        // Clear previous QR code
        if (this.qrCode) {
            this.canvas.innerHTML = '';
        }
        
        // UPI payment string format
        const upiString = this.buildUPIString(upiId, amount, ngoName);
        
        // Generate QR code
        this.qrCode = new QRCode(this.canvas, {
            text: upiString,
            width: 256,
            height: 256,
            colorDark: '#1E3A8A',
            colorLight: '#FFFFFF',
            correctLevel: QRCode.CorrectLevel.H
        });
        
        return upiString;
    }
    
    /**
     * Build UPI payment string
     * Format: upi://pay?pa=UPI_ID&pn=NAME&am=AMOUNT&cu=INR&tn=NOTE
     */
    buildUPIString(upiId, amount, ngoName) {
        const params = {
            pa: upiId,                    // Payee address (UPI ID)
            pn: encodeURIComponent(ngoName), // Payee name
            am: amount.toFixed(2),        // Amount
            cu: 'INR',                    // Currency
            tn: encodeURIComponent('Donation to ' + ngoName) // Transaction note
        };
        
        const queryString = Object.entries(params)
            .map(([key, value]) => `${key}=${value}`)
            .join('&');
        
        return `upi://pay?${queryString}`;
    }
    
    /**
     * Validate UPI ID format
     */
    static validateUPIId(upiId) {
        // Format: username@bankname
        const upiRegex = /^[\w.-]+@[\w.-]+$/;
        return upiRegex.test(upiId);
    }
    
    /**
     * Download QR code as image
     */
    downloadQR(filename = 'donation-qr.png') {
        const canvas = this.canvas.querySelector('canvas');
        if (!canvas) return;
        
        const link = document.createElement('a');
        link.download = filename;
        link.href = canvas.toDataURL('image/png');
        link.click();
    }
}

// Example usage:
// const qrGen = new UPIQRGenerator('qrCanvas');
// qrGen.generate('ngo@upi', 1000, 'Save Children Foundation');
