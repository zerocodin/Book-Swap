import 'package:flutter/material.dart';
import 'dart:io';
import 'package:image_picker/image_picker.dart';
import 'package:book_swaps/service/post_service.dart';
import 'package:book_swaps/user_model/user_preferences.dart';

class CreatePostForm extends StatefulWidget {
  final Function() onClose;
  final Function() onPostCreated;

  const CreatePostForm({
    super.key,
    required this.onClose,
    required this.onPostCreated,
  });

  @override
  State<CreatePostForm> createState() => _CreatePostFormState();
}

class _CreatePostFormState extends State<CreatePostForm> {
  final _postFormKey = GlobalKey<FormState>();
  final ImagePicker _picker = ImagePicker();

  String? _bookTitle;
  String? _authorName;
  String? _description;
  String? _batchYear;
  String? _department;
  String? _location;
  String? _contactNumber;
  String? _price;

  // Change from bool to String for post type
  String _postType = 'SELL'; // 'SELL', 'GIVE', or 'REQUEST'
  bool _anonymous = false;
  File? _bookImage;
  bool _isLoading = false;

  Future<void> _pickImage() async {
    final XFile? image = await _picker.pickImage(source: ImageSource.gallery);
    if (image != null) {
      setState(() {
        _bookImage = File(image.path);
      });
    }
  }

  void _showSnackBar(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
      ),
    );
  }

  Future<void> _submitPost() async {
    if (!_postFormKey.currentState!.validate()) return;

    _postFormKey.currentState!.save();
    setState(() => _isLoading = true);

    try {
      // Get current user
      final user = await RememberUser.getRememberUser();
      if (user == null) {
        _showSnackBar('User not found. Please login again.', isError: true);
        return;
      }

      // Create post data
      final postData = {
        'user_id': user.id,
        'title': _bookTitle!,
        'book_name': _bookTitle,
        'author': _authorName,
        'type': _postType, // Use the selected post type
        'description': _description ?? '',
        'is_anonymous': _anonymous ? 1 : 0,
        'batch_year': _batchYear,
        'department': _department,
        'location': _location,
        'contact_number': _contactNumber,
        // Only include price for SELL type
        'price': _postType == 'SELL' ? (_price ?? '0') : null,
      };

      // Remove null values
      postData.removeWhere((key, value) => value == null || value == '');

      // Create post
      final result = await PostService.createPost(postData);

      if (result['success'] == true) {
        final postId = result['post_id'];

        // Upload image if selected
        if (_bookImage != null && postId != null) {
          final imageResult = await PostService.uploadPostImage(postId, _bookImage!.path);
          if (imageResult['success'] == true) {
            _showSnackBar('Post created with image successfully!');
          } else {
            _showSnackBar('Post created but image upload failed: ${imageResult['message']}', isError: true);
          }
        } else {
          _showSnackBar('Post created successfully!');
        }

        widget.onPostCreated();
      } else {
        _showSnackBar(result['message'] ?? 'Failed to create post', isError: true);
      }
    } catch (e) {
      _showSnackBar('Error: $e', isError: true);
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Widget _buildFormFields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        TextFormField(
          decoration: const InputDecoration(
            labelText: 'Book Title *',
          ),
          validator: (value) {
            if (value == null || value.isEmpty) {
              return 'Please enter book title';
            }
            return null;
          },
          onSaved: (value) => _bookTitle = value,
        ),
        const SizedBox(height: 10),
        TextFormField(
          decoration: const InputDecoration(
            labelText: 'Author Name *',
          ),
          validator: (value) {
            if (value == null || value.isEmpty) {
              return 'Please enter author name';
            }
            return null;
          },
          onSaved: (value) => _authorName = value,
        ),
        const SizedBox(height: 10),
        TextFormField(
          decoration: const InputDecoration(
            labelText: 'Batch Year',
          ),
          onSaved: (value) => _batchYear = value,
        ),
        const SizedBox(height: 10),
        TextFormField(
          decoration: const InputDecoration(
            labelText: 'Department',
          ),
          onSaved: (value) => _department = value,
        ),
        const SizedBox(height: 10),
        TextFormField(
          decoration: const InputDecoration(
            labelText: 'Contact Number',
          ),
          keyboardType: TextInputType.phone,
          onSaved: (value) => _contactNumber = value,
        ),
      ],
    );
  }

  Widget _buildImageSection() {
    return Column(
      children: [
        GestureDetector(
          onTap: _pickImage,
          child: Container(
            width: double.infinity,
            height: 200,
            decoration: BoxDecoration(
              border: Border.all(color: Colors.grey),
              borderRadius: BorderRadius.circular(10),
            ),
            child: _bookImage != null
                ? ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: Image.file(_bookImage!, fit: BoxFit.cover),
            )
                : const Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.camera_alt, size: 40),
                SizedBox(height: 10),
                Text('Add Book Image'),
              ],
            ),
          ),
        ),
        const SizedBox(height: 10),
        const Text(
          'Square Image Recommended (Max 5MB)',
          style: TextStyle(fontSize: 12, color: Colors.grey),
          textAlign: TextAlign.center,
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Expanded(
                child: Text(
                  'Create Post',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              IconButton(
                icon: const Icon(Icons.close),
                onPressed: _isLoading ? null : widget.onClose,
              ),
            ],
          ),
          const Divider(),
          const SizedBox(height: 20),

          Form(
            key: _postFormKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Create New Book Post',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 15),

                LayoutBuilder(
                  builder: (context, constraints) {
                    bool useColumnLayout = constraints.maxWidth < 600;

                    if (useColumnLayout) {
                      return Column(
                        children: [
                          _buildFormFields(),
                          const SizedBox(height: 15),
                          _buildImageSection(),
                        ],
                      );
                    } else {
                      return Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(flex: 2, child: _buildFormFields()),
                          const SizedBox(width: 15),
                          Expanded(flex: 1, child: _buildImageSection()),
                        ],
                      );
                    }
                  },
                ),

                const SizedBox(height: 15),
                TextFormField(
                  decoration: const InputDecoration(
                    labelText: 'Location',
                  ),
                  onSaved: (value) => _location = value,
                ),
                const SizedBox(height: 10),
                TextFormField(
                  decoration: const InputDecoration(
                    labelText: 'Short Description',
                  ),
                  maxLines: 3,
                  onSaved: (value) => _description = value,
                ),

                // Price field - only show for SELL type
                if (_postType == 'SELL') ...[
                  const SizedBox(height: 10),
                  TextFormField(
                    decoration: const InputDecoration(
                      labelText: 'Price (৳)',
                      prefixText: '৳ ',
                    ),
                    keyboardType: TextInputType.number,
                    onSaved: (value) => _price = value,
                    validator: (value) {
                      if (_postType == 'SELL' && (value == null || value.isEmpty)) {
                        return 'Please enter price';
                      }
                      return null;
                    },
                  ),
                ],

                const SizedBox(height: 15),

                // Post Type Selection
                const Text(
                  'Post Type:',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    ChoiceChip(
                      label: const Text('Sell Book'),
                      selected: _postType == 'SELL',
                      onSelected: _isLoading ? null : (selected) {
                        if (selected) {
                          setState(() {
                            _postType = 'SELL';
                          });
                        }
                      },
                      selectedColor: Colors.green.withOpacity(0.2),
                      backgroundColor: Colors.grey.withOpacity(0.1),
                    ),
                    ChoiceChip(
                      label: const Text('Give Away'),
                      selected: _postType == 'GIVE',
                      onSelected: _isLoading ? null : (selected) {
                        if (selected) {
                          setState(() {
                            _postType = 'GIVE';
                          });
                        }
                      },
                      selectedColor: Colors.blue.withOpacity(0.2),
                      backgroundColor: Colors.grey.withOpacity(0.1),
                    ),
                    ChoiceChip(
                      label: const Text('Request Book'),
                      selected: _postType == 'REQUEST',
                      onSelected: _isLoading ? null : (selected) {
                        if (selected) {
                          setState(() {
                            _postType = 'REQUEST';
                          });
                        }
                      },
                      selectedColor: Colors.orange.withOpacity(0.2),
                      backgroundColor: Colors.grey.withOpacity(0.1),
                    ),
                  ],
                ),

                const SizedBox(height: 15),

                Row(
                  children: [
                    Checkbox(
                      value: _anonymous,
                      onChanged: _isLoading ? null : (value) {
                        setState(() {
                          _anonymous = value ?? false;
                        });
                      },
                    ),
                    const Text('Post Anonymously'),
                  ],
                ),

                // Hint text for Request type
                if (_postType == 'REQUEST')
                  Container(
                    padding: const EdgeInsets.all(10),
                    margin: const EdgeInsets.only(top: 10),
                    decoration: BoxDecoration(
                      color: Colors.orange.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.orange.withOpacity(0.3)),
                    ),
                    child: const Row(
                      children: [
                        Icon(Icons.info_outline, color: Colors.orange, size: 16),
                        SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'You are requesting this book. Others can contact you if they have it.',
                            style: TextStyle(fontSize: 12, color: Colors.orange),
                          ),
                        ),
                      ],
                    ),
                  ),

                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: _isLoading
                      ? const Center(child: CircularProgressIndicator())
                      : ElevatedButton(
                    onPressed: _submitPost,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: _postType == 'REQUEST'
                          ? Colors.orange
                          : (_postType == 'GIVE' ? Colors.blue : Colors.green),
                      padding: const EdgeInsets.symmetric(vertical: 15),
                    ),
                    child: Text(
                      _postType == 'REQUEST'
                          ? 'Request Book'
                          : (_postType == 'GIVE' ? 'Give Away Book' : 'Sell Book'),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}