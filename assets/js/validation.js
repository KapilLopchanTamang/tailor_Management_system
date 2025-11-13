/**
 * Form Validation JavaScript
 * Tailoring Management System
 * Uses jQuery Validation Plugin
 */

(function() {
    'use strict';
    
    // Wait for DOM to be ready
    $(document).ready(function() {
        
        // Login Form Validation
        if ($('#loginForm').length) {
            $('#loginForm').validate({
                rules: {
                    email_or_username: {
                        required: true,
                        minlength: 3
                    },
                    password: {
                        required: true,
                        minlength: 6
                    }
                },
                messages: {
                    email_or_username: {
                        required: "Please enter your email or username",
                        minlength: "Email/username must be at least 3 characters"
                    },
                    password: {
                        required: "Please enter your password",
                        minlength: "Password must be at least 6 characters"
                    }
                },
                errorClass: 'is-invalid',
                validClass: 'is-valid',
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.after(error);
                },
                submitHandler: function(form) {
                    // Show loading spinner
                    const submitBtn = $(form).find('button[type="submit"]');
                    if (submitBtn.length && window.TMSSecurity) {
                        TMSSecurity.showLoading(submitBtn[0]);
                    }
                    form.submit();
                }
            });
        }
        
        // Registration Form Validation
        if ($('#registerForm').length) {
            $('#registerForm').validate({
                rules: {
                    name: {
                        required: true,
                        minlength: 2
                    },
                    email: {
                        required: true,
                        email: true
                    },
                    phone: {
                        required: true,
                        minlength: 10
                    },
                    password: {
                        required: true,
                        minlength: 6
                    },
                    confirm_password: {
                        required: true,
                        minlength: 6,
                        equalTo: '#password'
                    }
                },
                messages: {
                    name: {
                        required: "Please enter your full name",
                        minlength: "Name must be at least 2 characters"
                    },
                    email: {
                        required: "Please enter your email address",
                        email: "Please enter a valid email address"
                    },
                    phone: {
                        required: "Please enter your phone number",
                        minlength: "Phone number must be at least 10 digits"
                    },
                    password: {
                        required: "Please enter a password",
                        minlength: "Password must be at least 6 characters"
                    },
                    confirm_password: {
                        required: "Please confirm your password",
                        minlength: "Password must be at least 6 characters",
                        equalTo: "Passwords do not match"
                    }
                },
                errorClass: 'is-invalid',
                validClass: 'is-valid',
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.after(error);
                },
                submitHandler: function(form) {
                    const submitBtn = $(form).find('button[type="submit"]');
                    if (submitBtn.length && window.TMSSecurity) {
                        TMSSecurity.showLoading(submitBtn[0]);
                    }
                    form.submit();
                }
            });
        }
        
        // Feedback Form Validation
        if ($('#feedbackForm').length) {
            $('#feedbackForm').validate({
                rules: {
                    order_id: {
                        required: true
                    },
                    rating: {
                        required: true,
                        min: 1,
                        max: 5
                    },
                    comment: {
                        maxlength: 1000
                    }
                },
                messages: {
                    order_id: {
                        required: "Please select an order"
                    },
                    rating: {
                        required: "Please select a rating",
                        min: "Rating must be at least 1",
                        max: "Rating must be at most 5"
                    },
                    comment: {
                        maxlength: "Comment must be less than 1000 characters"
                    }
                },
                errorClass: 'is-invalid',
                validClass: 'is-valid',
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.after(error);
                },
                submitHandler: function(form) {
                    const submitBtn = $(form).find('button[type="submit"]');
                    if (submitBtn.length && window.TMSSecurity) {
                        TMSSecurity.showLoading(submitBtn[0]);
                    }
                    form.submit();
                }
            });
        }
        
        // Order Form Validation
        if ($('#newOrderForm').length) {
            $('#newOrderForm').validate({
                rules: {
                    description: {
                        required: true,
                        minlength: 10
                    },
                    delivery_date: {
                        date: true
                    }
                },
                messages: {
                    description: {
                        required: "Please provide an order description",
                        minlength: "Description must be at least 10 characters"
                    },
                    delivery_date: {
                        date: "Please enter a valid date"
                    }
                },
                errorClass: 'is-invalid',
                validClass: 'is-valid',
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.after(error);
                },
                submitHandler: function(form) {
                    // Check if items are selected
                    const orderItemsInput = $('#orderItemsInput');
                    if (!orderItemsInput.val() || orderItemsInput.val() === '[]') {
                        alert('Please add at least one item to your order.');
                        return false;
                    }
                    
                    const submitBtn = $(form).find('button[type="submit"]');
                    if (submitBtn.length && window.TMSSecurity) {
                        TMSSecurity.showLoading(submitBtn[0]);
                    }
                    form.submit();
                }
            });
        }
        
        // Profile Form Validation
        if ($('#profileForm').length) {
            $('#profileForm').validate({
                rules: {
                    name: {
                        required: true,
                        minlength: 2
                    },
                    phone: {
                        required: true,
                        minlength: 10
                    },
                    email: {
                        email: true
                    }
                },
                messages: {
                    name: {
                        required: "Please enter your name",
                        minlength: "Name must be at least 2 characters"
                    },
                    phone: {
                        required: "Please enter your phone number",
                        minlength: "Phone number must be at least 10 digits"
                    },
                    email: {
                        email: "Please enter a valid email address"
                    }
                },
                errorClass: 'is-invalid',
                validClass: 'is-valid',
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.after(error);
                },
                submitHandler: function(form) {
                    const submitBtn = $(form).find('button[type="submit"]');
                    if (submitBtn.length && window.TMSSecurity) {
                        TMSSecurity.showLoading(submitBtn[0]);
                    }
                    form.submit();
                }
            });
        }
        
        // Add User Form Validation (Admin)
        if ($('#addUserForm').length) {
            $('#addUserForm').validate({
                rules: {
                    username: {
                        required: true,
                        minlength: 3
                    },
                    email: {
                        required: true,
                        email: true
                    },
                    password: {
                        required: true,
                        minlength: 6
                    },
                    role: {
                        required: true
                    }
                },
                messages: {
                    username: {
                        required: "Please enter a username",
                        minlength: "Username must be at least 3 characters"
                    },
                    email: {
                        required: "Please enter an email address",
                        email: "Please enter a valid email address"
                    },
                    password: {
                        required: "Please enter a password",
                        minlength: "Password must be at least 6 characters"
                    },
                    role: {
                        required: "Please select a role"
                    }
                },
                errorClass: 'is-invalid',
                validClass: 'is-valid',
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.after(error);
                }
            });
        }
        
        // Edit User Form Validation (Admin)
        if ($('#editUserForm').length) {
            $('#editUserForm').validate({
                rules: {
                    username: {
                        required: true,
                        minlength: 3
                    },
                    email: {
                        required: true,
                        email: true
                    },
                    password: {
                        minlength: 6
                    },
                    role: {
                        required: true
                    }
                },
                messages: {
                    username: {
                        required: "Please enter a username",
                        minlength: "Username must be at least 3 characters"
                    },
                    email: {
                        required: "Please enter an email address",
                        email: "Please enter a valid email address"
                    },
                    password: {
                        minlength: "Password must be at least 6 characters"
                    },
                    role: {
                        required: "Please select a role"
                    }
                },
                errorClass: 'is-invalid',
                validClass: 'is-valid',
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.after(error);
                }
            });
        }
        
        // Add Inventory Item Form Validation
        if ($('#addItemForm').length) {
            $('#addItemForm').validate({
                rules: {
                    item_name: {
                        required: true,
                        minlength: 2
                    },
                    type: {
                        required: true
                    },
                    quantity: {
                        required: true,
                        number: true,
                        min: 0
                    },
                    price: {
                        required: true,
                        number: true,
                        min: 0
                    },
                    unit: {
                        required: true
                    }
                },
                messages: {
                    item_name: {
                        required: "Please enter item name",
                        minlength: "Item name must be at least 2 characters"
                    },
                    type: {
                        required: "Please select item type"
                    },
                    quantity: {
                        required: "Please enter quantity",
                        number: "Please enter a valid number",
                        min: "Quantity must be 0 or greater"
                    },
                    price: {
                        required: "Please enter price",
                        number: "Please enter a valid number",
                        min: "Price must be 0 or greater"
                    },
                    unit: {
                        required: "Please enter unit"
                    }
                },
                errorClass: 'is-invalid',
                validClass: 'is-valid',
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.after(error);
                }
            });
        }
        
        // Edit Inventory Item Form Validation
        if ($('#editItemForm').length) {
            $('#editItemForm').validate({
                rules: {
                    item_name: {
                        required: true,
                        minlength: 2
                    },
                    type: {
                        required: true
                    },
                    quantity: {
                        required: true,
                        number: true,
                        min: 0
                    },
                    price: {
                        required: true,
                        number: true,
                        min: 0
                    },
                    unit: {
                        required: true
                    }
                },
                messages: {
                    item_name: {
                        required: "Please enter item name",
                        minlength: "Item name must be at least 2 characters"
                    },
                    type: {
                        required: "Please select item type"
                    },
                    quantity: {
                        required: "Please enter quantity",
                        number: "Please enter a valid number",
                        min: "Quantity must be 0 or greater"
                    },
                    price: {
                        required: "Please enter price",
                        number: "Please enter a valid number",
                        min: "Price must be 0 or greater"
                    },
                    unit: {
                        required: "Please enter unit"
                    }
                },
                errorClass: 'is-invalid',
                validClass: 'is-valid',
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.after(error);
                }
            });
        }
        
        // Payment Form Validation
        if ($('#recordPaymentForm').length) {
            $('#recordPaymentForm').validate({
                rules: {
                    amount: {
                        required: true,
                        number: true,
                        min: 0.01
                    },
                    payment_method: {
                        required: true
                    }
                },
                messages: {
                    amount: {
                        required: "Please enter payment amount",
                        number: "Please enter a valid number",
                        min: "Amount must be greater than 0"
                    },
                    payment_method: {
                        required: "Please select payment method"
                    }
                },
                errorClass: 'is-invalid',
                validClass: 'is-valid',
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.after(error);
                }
            });
        }
        
        // Real-time validation feedback
        $('input, textarea, select').on('blur', function() {
            if ($(this).hasClass('validate')) {
                $(this).valid();
            }
        });
        
        // Custom validation for email fields
        $.validator.addMethod("email", function(value, element) {
            return this.optional(element) || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        }, "Please enter a valid email address");
        
        // Custom validation for phone numbers
        $.validator.addMethod("phone", function(value, element) {
            return this.optional(element) || /^[0-9+\-\s()]+$/.test(value) && value.replace(/[^0-9]/g, '').length >= 10;
        }, "Please enter a valid phone number");
        
    });
})();

