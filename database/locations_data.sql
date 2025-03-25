-- Insert data for divisions
INSERT INTO divisions (name, bn_name) VALUES
('Barishal', 'বরিশাল'),
('Chattogram', 'চট্টগ্রাম'),
('Dhaka', 'ঢাকা'),
('Khulna', 'খুলনা'),
('Rajshahi', 'রাজশাহী'),
('Rangpur', 'রংপুর'),
('Sylhet', 'সিলেট'),
('Mymensingh', 'ময়মনসিংহ');

-- Insert data for districts
INSERT INTO districts (division_id, name, bn_name) VALUES
-- Barishal Division
(1, 'Barguna', 'বরগুনা'),
(1, 'Barishal', 'বরিশাল'),
(1, 'Bhola', 'ভোলা'),
(1, 'Jhalokati', 'ঝালকাঠি'),
(1, 'Patuakhali', 'পটুয়াখালী'),
(1, 'Pirojpur', 'পিরোজপুর'),

-- Chattogram Division
(2, 'Bandarban', 'বান্দরবান'),
(2, 'Brahmanbaria', 'ব্রাহ্মণবাড়িয়া'),
(2, 'Chandpur', 'চাঁদপুর'),
(2, 'Chattogram', 'চট্টগ্রাম'),
(2, 'Cumilla', 'কুমিল্লা'),
(2, 'Cox\'s Bazar', 'কক্সবাজার'),
(2, 'Feni', 'ফেনী'),
(2, 'Khagrachhari', 'খাগড়াছড়ি'),
(2, 'Lakshmipur', 'লক্ষ্মীপুর'),
(2, 'Noakhali', 'নোয়াখালী'),
(2, 'Rangamati', 'রাঙ্গামাটি'),

-- Dhaka Division
(3, 'Dhaka', 'ঢাকা'),
(3, 'Faridpur', 'ফরিদপুর'),
(3, 'Gazipur', 'গাজীপুর'),
(3, 'Gopalganj', 'গোপালগঞ্জ'),
(3, 'Kishoreganj', 'কিশোরগঞ্জ'),
(3, 'Madaripur', 'মাদারীপুর'),
(3, 'Manikganj', 'মানিকগঞ্জ'),
(3, 'Munshiganj', 'মুন্সিগঞ্জ'),
(3, 'Narayanganj', 'নারায়ণগঞ্জ'),
(3, 'Narsingdi', 'নরসিংদী'),
(3, 'Rajbari', 'রাজবাড়ী'),
(3, 'Shariatpur', 'শরীয়তপুর'),
(3, 'Tangail', 'টাঙ্গাইল'),

-- Khulna Division
(4, 'Bagerhat', 'বাগেরহাট'),
(4, 'Chuadanga', 'চুয়াডাঙ্গা'),
(4, 'Jashore', 'যশোর'),
(4, 'Jhenaidah', 'ঝিনাইদহ'),
(4, 'Khulna', 'খুলনা'),
(4, 'Kushtia', 'কুষ্টিয়া'),
(4, 'Magura', 'মাগুরা'),
(4, 'Meherpur', 'মেহেরপুর'),
(4, 'Narail', 'নড়াইল'),
(4, 'Satkhira', 'সাতক্ষীরা'),

-- Rajshahi Division
(5, 'Bogura', 'বগুড়া'),
(5, 'Chapainawabganj', 'চাঁপাইনবাবগঞ্জ'),
(5, 'Joypurhat', 'জয়পুরহাট'),
(5, 'Naogaon', 'নওগাঁ'),
(5, 'Natore', 'নাটোর'),
(5, 'Pabna', 'পাবনা'),
(5, 'Rajshahi', 'রাজশাহী'),
(5, 'Sirajganj', 'সিরাজগঞ্জ'),

-- Rangpur Division
(6, 'Dinajpur', 'দিনাজপুর'),
(6, 'Gaibandha', 'গাইবান্ধা'),
(6, 'Kurigram', 'কুড়িগ্রাম'),
(6, 'Lalmonirhat', 'লালমনিরহাট'),
(6, 'Nilphamari', 'নীলফামারী'),
(6, 'Panchagarh', 'পঞ্চগড়'),
(6, 'Rangpur', 'রংপুর'),
(6, 'Thakurgaon', 'ঠাকুরগাঁও'),

-- Sylhet Division
(7, 'Habiganj', 'হবিগঞ্জ'),
(7, 'Moulvibazar', 'মৌলভীবাজার'),
(7, 'Sunamganj', 'সুনামগঞ্জ'),
(7, 'Sylhet', 'সিলেট'),

-- Mymensingh Division
(8, 'Jamalpur', 'জামালপুর'),
(8, 'Mymensingh', 'ময়মনসিংহ'),
(8, 'Netrokona', 'নেত্রকোণা'),
(8, 'Sherpur', 'শেরপুর');

-- Insert data for upazilas
INSERT INTO upazilas (district_id, name, bn_name) VALUES
-- Barguna District (id: 1)
(1, 'Amtali', 'আমতলী'),
(1, 'Bamna', 'বামনা'),
(1, 'Barguna Sadar', 'বরগুনা সদর'),
(1, 'Betagi', 'বেতাগী'),
(1, 'Patharghata', 'পাথরঘাটা'),
(1, 'Taltali', 'তালতলী'),

-- Barishal District (id: 2)
(2, 'Agailjhara', 'আগৈলঝাড়া'),
(2, 'Babuganj', 'বাবুগঞ্জ'),
(2, 'Bakerganj', 'বাকেরগঞ্জ'),
(2, 'Banaripara', 'বানারীপাড়া'),
(2, 'Barishal Sadar', 'বরিশাল সদর'),
(2, 'Gournadi', 'গৌরনদী'),
(2, 'Hizla', 'হিজলা'),
(2, 'Mehendiganj', 'মেহেন্দিগঞ্জ'),
(2, 'Muladi', 'মুলাদী'),
(2, 'Wazirpur', 'উজিরপুর'),

-- Bhola District (id: 3)
(3, 'Bhola Sadar', 'ভোলা সদর'),
(3, 'Borhanuddin', 'বোরহানউদ্দিন'),
(3, 'Char Fasson', 'চর ফ্যাশন'),
(3, 'Daulatkhan', 'দৌলতখান'),
(3, 'Lalmohan', 'লালমোহন'),
(3, 'Manpura', 'মনপুরা'),
(3, 'Tazumuddin', 'তজুমুদ্দিন'),

-- Jhalokati District (id: 4)
(4, 'Jhalokati Sadar', 'ঝালকাঠি সদর'),
(4, 'Kathalia', 'কাঠালিয়া'),
(4, 'Nalchity', 'নলছিটি'),
(4, 'Rajapur', 'রাজাপুর'),

-- Patuakhali District (id: 5)
(5, 'Bauphal', 'বাউফল'),
(5, 'Dashmina', 'দশমিনা'),
(5, 'Dumki', 'দুমকি'),
(5, 'Galachipa', 'গলাচিপা'),
(5, 'Kalapara', 'কলাপাড়া'),
(5, 'Mirzaganj', 'মির্জাগঞ্জ'),
(5, 'Patuakhali Sadar', 'পটুয়াখালী সদর'),
(5, 'Rangabali', 'রাঙ্গাবালী'),

-- Pirojpur District (id: 6)
(6, 'Bhandaria', 'ভান্ডারিয়া'),
(6, 'Kawkhali', 'কাউখালী'),
(6, 'Mathbaria', 'মঠবাড়িয়া'),
(6, 'Nazirpur', 'নাজিরপুর'),
(6, 'Nesarabad', 'নেসারাবাদ'),
(6, 'Pirojpur Sadar', 'পিরোজপুর সদর'),
(6, 'Indurkani', 'ইন্দুরকানি'),

-- Bandarban District (id: 7)
(7, 'Alikadam', 'আলীকদম'),
(7, 'Bandarban Sadar', 'বান্দরবান সদর'),
(7, 'Lama', 'লামা'),
(7, 'Naikhongchhari', 'নাইক্ষ্যংছড়ি'),
(7, 'Rowangchhari', 'রোয়াংছড়ি'),
(7, 'Ruma', 'রুমা'),
(7, 'Thanchi', 'থানচি'),

-- Brahmanbaria District (id: 8)
(8, 'Akhaura', 'আখাউড়া'),
(8, 'Ashuganj', 'আশুগঞ্জ'),
(8, 'Bancharampur', 'বাঞ্ছারামপুর'),
(8, 'Brahmanbaria Sadar', 'ব্রাহ্মণবাড়িয়া সদর'),
(8, 'Kasba', 'কসবা'),
(8, 'Nabinagar', 'নবীনগর'),
(8, 'Nasirnagar', 'নাসিরনগর'),
(8, 'Sarail', 'সরাইল'),
(8, 'Bijoynagar', 'বিজয়নগর'),

-- Chandpur District (id: 9)
(9, 'Chandpur Sadar', 'চাঁদপুর সদর'),
(9, 'Faridganj', 'ফরিদগঞ্জ'),
(9, 'Haimchar', 'হাইমচর'),
(9, 'Haziganj', 'হাজীগঞ্জ'),
(9, 'Kachua', 'কচুয়া'),
(9, 'Matlab Dakshin', 'মতলব দক্ষিণ'),
(9, 'Matlab Uttar', 'মতলব উত্তর'),
(9, 'Shahrasti', 'শাহরাস্তি'),

-- Chattogram District (id: 10)
(10, 'Anwara', 'আনোয়ারা'),
(10, 'Banshkhali', 'বাঁশখালী'),
(10, 'Boalkhali', 'বোয়ালখালী'),
(10, 'Chandanaish', 'চন্দনাইশ'),
(10, 'Fatikchhari', 'ফটিকছড়ি'),
(10, 'Hathazari', 'হাটহাজারী'),
(10, 'Lohagara', 'লোহাগাড়া'),
(10, 'Mirsharai', 'মীরসরাই'),
(10, 'Patiya', 'পটিয়া'),
(10, 'Rangunia', 'রাঙ্গুনিয়া'),
(10, 'Raozan', 'রাউজান'),
(10, 'Sandwip', 'সন্দ্বীপ'),
(10, 'Satkania', 'সাতকানিয়া'),
(10, 'Sitakunda', 'সীতাকুন্ড'),
(10, 'Karnafuli', 'কর্ণফুলী'),

-- Cumilla District (id: 11)
(11, 'Barura', 'বরুড়া'),
(11, 'Brahmanpara', 'ব্রাহ্মণপাড়া'),
(11, 'Burichang', 'বুড়িচং'),
(11, 'Chandina', 'চান্দিনা'),
(11, 'Chauddagram', 'চৌদ্দগ্রাম'),
(11, 'Cumilla Sadar', 'কুমিল্লা সদর'),
(11, 'Daudkandi', 'দাউদকান্দি'),
(11, 'Debidwar', 'দেবিদ্বার'),
(11, 'Homna', 'হোমনা'),
(11, 'Laksam', 'লাকসাম'),
(11, 'Lalmai', 'লালমাই'),
(11, 'Meghna', 'মেঘনা'),
(11, 'Monohargonj', 'মনোহরগঞ্জ'),
(11, 'Muradnagar', 'মুরাদনগর'),
(11, 'Nangalkot', 'নাঙ্গলকোট'),
(11, 'Titas', 'তিতাস'),
(11, 'Sadarsouth', 'সদর দক্ষিণ'),

-- Cox's Bazar District (id: 12)
(12, 'Chakaria', 'চকরিয়া'),
(12, 'Cox\'s Bazar Sadar', 'কক্সবাজার সদর'),
(12, 'Kutubdia', 'কুতুবদিয়া'),
(12, 'Maheshkhali', 'মহেশখালী'),
(12, 'Pekua', 'পেকুয়া'),
(12, 'Ramu', 'রামু'),
(12, 'Teknaf', 'টেকনাফ'),
(12, 'Ukhia', 'উখিয়া'),

-- Feni District (id: 13)
(13, 'Chhagalnaiya', 'ছাগলনাইয়া'),
(13, 'Daganbhuiyan', 'দাগনভূঞা'),
(13, 'Feni Sadar', 'ফেনী সদর'),
(13, 'Fulgazi', 'ফুলগাজী'),
(13, 'Parshuram', 'পরশুরাম'),
(13, 'Sonagazi', 'সোনাগাজী'),

-- Khagrachhari District (id: 14)
(14, 'Dighinala', 'দিঘীনালা'),
(14, 'Khagrachhari Sadar', 'খাগড়াছড়ি সদর'),
(14, 'Lakshmichhari', 'লক্ষ্মীছড়ি'),
(14, 'Mahalchhari', 'মহালছড়ি'),
(14, 'Manikchhari', 'মানিকছড়ি'),
(14, 'Matiranga', 'মাটিরাঙ্গা'),
(14, 'Panchhari', 'পানছড়ি'),
(14, 'Ramgarh', 'রামগড়'),
(14, 'Guimara', 'গুইমারা'),

-- Lakshmipur District (id: 15)
(15, 'Kamalnagar', 'কমলনগর'),
(15, 'Lakshmipur Sadar', 'লক্ষ্মীপুর সদর'),
(15, 'Raipur', 'রায়পুর'),
(15, 'Ramganj', 'রামগঞ্জ'),
(15, 'Ramgati', 'রামগতি'),

-- Noakhali District (id: 16)
(16, 'Begumganj', 'বেগমগঞ্জ'),
(16, 'Chatkhil', 'চাটখিল'),
(16, 'Companiganj', 'কোম্পানীগঞ্জ'),
(16, 'Hatiya', 'হাতিয়া'),
(16, 'Kabirhat', 'কবিরহাট'),
(16, 'Noakhali Sadar', 'নোয়াখালী সদর'),
(16, 'Senbagh', 'সেনবাগ'),
(16, 'Sonaimuri', 'সোনাইমুড়ি'),
(16, 'Subarnachar', 'সুবর্ণচর'),

-- Rangamati District (id: 17)
(17, 'Baghaichhari', 'বাঘাইছড়ি'),
(17, 'Barkal', 'বরকল'),
(17, 'Belaichhari', 'বিলাইছড়ি'),
(17, 'Juraichhari', 'জুরাছড়ি'),
(17, 'Kaptai', 'কাপ্তাই'),
(17, 'Kawkhali', 'কাউখালী'),
(17, 'Langadu', 'লংগদু'),
(17, 'Naniarchar', 'নানিয়ারচর'),
(17, 'Rajasthali', 'রাজস্থলী'),
(17, 'Rangamati Sadar', 'রাঙ্গামাটি সদর'),

-- Dhaka District (id: 18)
(18, 'Dhamrai', 'ধামরাই'),
(18, 'Dohar', 'দোহার'),
(18, 'Keraniganj', 'কেরাণীগঞ্জ'),
(18, 'Nawabganj', 'নবাবগঞ্জ'),
(18, 'Savar', 'সাভার'),

-- Faridpur District (id: 19)
(19, 'Alfadanga', 'আলফাডাঙ্গা'),
(19, 'Bhanga', 'ভাঙ্গা'),
(19, 'Boalmari', 'বোয়ালমারী'),
(19, 'Charbhadrasan', 'চরভদ্রাসন'),
(19, 'Faridpur Sadar', 'ফরিদপুর সদর'),
(19, 'Madhukhali', 'মধুখালী'),
(19, 'Nagarkanda', 'নগরকান্দা'),
(19, 'Sadarpur', 'সদরপুর'),
(19, 'Saltha', 'সালথা'),

-- Gazipur District (id: 20)
(20, 'Gazipur Sadar', 'গাজীপুর সদর'),
(20, 'Kaliakair', 'কালিয়াকৈর'),
(20, 'Kaliganj', 'কালীগঞ্জ'),
(20, 'Kapasia', 'কাপাসিয়া'),
(20, 'Sreepur', 'শ্রীপুর'),

-- Gopalganj District (id: 21)
(21, 'Gopalganj Sadar', 'গোপালগঞ্জ সদর'),
(21, 'Kashiani', 'কাশিয়ানী'),
(21, 'Kotalipara', 'কোটালীপাড়া'),
(21, 'Muksudpur', 'মুকসুদপুর'),
(21, 'Tungipara', 'টুংগীপাড়া'),

-- Kishoreganj District (id: 22)
(22, 'Austagram', 'অষ্টগ্রাম'),
(22, 'Bajitpur', 'বাজিতপুর'),
(22, 'Bhairab', 'ভৈরব'),
(22, 'Hossainpur', 'হোসেনপুর'),
(22, 'Itna', 'ইটনা'),
(22, 'Karimganj', 'করিমগঞ্জ'),
(22, 'Katiadi', 'কটিয়াদী'),
(22, 'Kishoreganj Sadar', 'কিশোরগঞ্জ সদর'),
(22, 'Kuliarchar', 'কুলিয়ারচর'),
(22, 'Mithamain', 'মিঠামইন'),
(22, 'Nikli', 'নিকলী'),
(22, 'Pakundia', 'পাকুন্দিয়া'),
(22, 'Tarail', 'তাড়াইল'),

-- Madaripur District (id: 23)
(23, 'Kalkini', 'কালকিনি'),
(23, 'Madaripur Sadar', 'মাদারীপুর সদর'),
(23, 'Rajoir', 'রাজৈর'),
(23, 'Shibchar', 'শিবচর'),
(23, 'Dasar', 'ডাসার'),

-- Manikganj District (id: 24)
(24, 'Daulatpur', 'দৌলতপুর'),
(24, 'Ghior', 'ঘিওর'),
(24, 'Harirampur', 'হরিরামপুর'),
(24, 'Manikganj Sadar', 'মানিকগঞ্জ সদর'),
(24, 'Saturia', 'সাটুরিয়া'),
(24, 'Shibalaya', 'শিবালয়'),
(24, 'Singair', 'সিঙ্গাইর'),

-- Munshiganj District (id: 25)
(25, 'Gazaria', 'গজারিয়া'),
(25, 'Louhajang', 'লৌহজং'),
(25, 'Munshiganj Sadar', 'মুন্সিগঞ্জ সদর'),
(25, 'Sirajdikhan', 'সিরাজদিখান'),
(25, 'Sreenagar', 'শ্রীনগর'),
(25, 'Tongibari', 'টংগীবাড়ি'),

-- Narayanganj District (id: 26)
(26, 'Araihazar', 'আড়াইহাজার'),
(26, 'Bandar', 'বন্দর'),
(26, 'Narayanganj Sadar', 'নারায়ণগঞ্জ সদর'),
(26, 'Rupganj', 'রূপগঞ্জ'),
(26, 'Sonargaon', 'সোনারগাঁও'),

-- Narsingdi District (id: 27)
(27, 'Belabo', 'বেলাবো'),
(27, 'Monohardi', 'মনোহরদী'),
(27, 'Narsingdi Sadar', 'নরসিংদী সদর'),
(27, 'Palash', 'পলাশ'),
(27, 'Raipura', 'রায়পুরা'),
(27, 'Shibpur', 'শিবপুর'),

-- Rajbari District (id: 28)
(28, 'Baliakandi', 'বালিয়াকান্দি'),
(28, 'Goalanda', 'গোয়ালন্দ'),
(28, 'Kalukhali', 'কালুখালী'),
(28, 'Pangsha', 'পাংশা'),
(28, 'Rajbari Sadar', 'রাজবাড়ী সদর'),

-- Shariatpur District (id: 29)
(29, 'Bhedarganj', 'ভেদরগঞ্জ'),
(29, 'Damudya', 'ডামুড্যা'),
(29, 'Gosairhat', 'গোসাইরহাট'),
(29, 'Naria', 'নড়িয়া'),
(29, 'Shariatpur Sadar', 'শরীয়তপুর সদর'),
(29, 'Zanjira', 'জাজিরা'),

-- Tangail District (id: 30)
(30, 'Basail', 'বাসাইল'),
(30, 'Bhuapur', 'ভুয়াপুর'),
(30, 'Delduar', 'দেলদুয়ার'),
(30, 'Dhanbari', 'ধনবাড়ী'),
(30, 'Ghatail', 'ঘাটাইল'),
(30, 'Gopalpur', 'গোপালপুর'),
(30, 'Kalihati', 'কালিহাতী'),
(30, 'Madhupur', 'মধুপুর'),
(30, 'Mirzapur', 'মির্জাপুর'),
(30, 'Nagarpur', 'নাগরপুর'),
(30, 'Sakhipur', 'সখিপুর'),
(30, 'Tangail Sadar', 'টাঙ্গাইল সদর'),

-- Bagerhat District (id: 31)
(31, 'Bagerhat Sadar', 'বাগেরহাট সদর'),
(31, 'Chitalmari', 'চিতলমারী'),
(31, 'Fakirhat', 'ফকিরহাট'),
(31, 'Kachua', 'কচুয়া'),
(31, 'Mollahat', 'মোল্লাহাট'),
(31, 'Mongla', 'মংলা'),
(31, 'Morrelganj', 'মোড়েলগঞ্জ'),
(31, 'Rampal', 'রামপাল'),
(31, 'Sarankhola', 'শরণখোলা'),

-- Chuadanga District (id: 32)
(32, 'Alamdanga', 'আলমডাঙ্গা'),
(32, 'Chuadanga Sadar', 'চুয়াডাঙ্গা সদর'),
(32, 'Damurhuda', 'দামুড়হুদা'),
(32, 'Jibannagar', 'জীবননগর'),

-- Jashore District (id: 33)
(33, 'Abhaynagar', 'অভয়নগর'),
(33, 'Bagherpara', 'বাঘারপাড়া'),
(33, 'Chaugachha', 'চৌগাছা'),
(33, 'Jashore Sadar', 'যশোর সদর'),
(33, 'Jhikargachha', 'ঝিকরগাছা'),
(33, 'Keshabpur', 'কেশবপুর'),
(33, 'Manirampur', 'মণিরামপুর'),
(33, 'Sharsha', 'শার্শা'),

-- Jhenaidah District (id: 34)
(34, 'Harinakunda', 'হরিণাকুন্ডু'),
(34, 'Jhenaidah Sadar', 'ঝিনাইদহ সদর'),
(34, 'Kaliganj', 'কালীগঞ্জ'),
(34, 'Kotchandpur', 'কোটচাঁদপুর'),
(34, 'Maheshpur', 'মহেশপুর'),
(34, 'Shailkupa', 'শৈলকুপা'),

-- Khulna District (id: 35)
(35, 'Batiaghata', 'বটিয়াঘাটা'),
(35, 'Dacope', 'দাকোপ'),
(35, 'Dighalia', 'দিঘলিয়া'),
(35, 'Dumuria', 'ডুমুরিয়া'),
(35, 'Koyra', 'কয়রা'),
(35, 'Paikgachha', 'পাইকগাছা'),
(35, 'Phultala', 'ফুলতলা'),
(35, 'Rupsa', 'রূপসা'),
(35, 'Terokhada', 'তেরখাদা'),

-- Kushtia District (id: 36)
(36, 'Bheramara', 'ভেড়ামারা'),
(36, 'Daulatpur', 'দৌলতপুর'),
(36, 'Khoksa', 'খোকসা'),
(36, 'Kumarkhali', 'কুমারখালী'),
(36, 'Kushtia Sadar', 'কুষ্টিয়া সদর'),
(36, 'Mirpur', 'মিরপুর'),

-- Magura District (id: 37)
(37, 'Magura Sadar', 'মাগুরা সদর'),
(37, 'Mohammadpur', 'মহম্মদপুর'),
(37, 'Shalikha', 'শালিখা'),
(37, 'Sreepur', 'শ্রীপুর'),

-- Meherpur District (id: 38)
(38, 'Gangni', 'গাংনী'),
(38, 'Meherpur Sadar', 'মেহেরপুর সদর'),
(38, 'Mujibnagar', 'মুজিবনগর'),

-- Narail District (id: 39)
(39, 'Kalia', 'কালিয়া'),
(39, 'Lohagara', 'লোহাগড়া'),
(39, 'Narail Sadar', 'নড়াইল সদর'),

-- Satkhira District (id: 40)
(40, 'Assasuni', 'আশাশুনি'),
(40, 'Debhata', 'দেবহাটা'),
(40, 'Kalaroa', 'কলারোয়া'),
(40, 'Kaliganj', 'কালীগঞ্জ'),
(40, 'Satkhira Sadar', 'সাতক্ষীরা সদর'),
(40, 'Shyamnagar', 'শ্যামনগর'),
(40, 'Tala', 'তালা'),

-- Bogura District (id: 41)
(41, 'Adamdighi', 'আদমদিঘি'),
(41, 'Bogura Sadar', 'বগুড়া সদর'),
(41, 'Dhunat', 'ধুনট'),
(41, 'Dhupchanchia', 'ধুপচাঁচিয়া'),
(41, 'Gabtali', 'গাবতলী'),
(41, 'Kahaloo', 'কাহালু'),
(41, 'Nandigram', 'নন্দিগ্রাম'),
(41, 'Sariakandi', 'সারিয়াকান্দি'),
(41, 'Shajahanpur', 'শাজাহানপুর'),
(41, 'Sherpur', 'শেরপুর'),
(41, 'Shibganj', 'শিবগঞ্জ'),
(41, 'Sonatala', 'সোনাতলা'),

-- Chapainawabganj District (id: 42)
(42, 'Bholahat', 'ভোলাহাট'),
(42, 'Chapainawabganj Sadar', 'চাঁপাইনবাবগঞ্জ সদর'),
(42, 'Gomastapur', 'গোমস্তাপুর'),
(42, 'Nachole', 'নাচোল'),
(42, 'Shibganj', 'শিবগঞ্জ'),

-- Joypurhat District (id: 43)
(43, 'Akkelpur', 'আক্কেলপুর'),
(43, 'Joypurhat Sadar', 'জয়পুরহাট সদর'),
(43, 'Kalai', 'কালাই'),
(43, 'Khetlal', 'ক্ষেতলাল'),
(43, 'Panchbibi', 'পাঁচবিবি'),

-- Naogaon District (id: 44)
(44, 'Atrai', 'আত্রাই'),
(44, 'Badalgachhi', 'বদলগাছী'),
(44, 'Dhamoirhat', 'ধামইরহাট'),
(44, 'Manda', 'মান্দা'),
(44, 'Mohadevpur', 'মহাদেবপুর'),
(44, 'Naogaon Sadar', 'নওগাঁ সদর'),
(44, 'Niamatpur', 'নিয়ামতপুর'),
(44, 'Patnitala', 'পত্নীতলা'),
(44, 'Porsha', 'পোরশা'),
(44, 'Raninagar', 'রাণীনগর'),
(44, 'Sapahar', 'সাপাহার'),

-- Natore District (id: 45)
(45, 'Bagatipara', 'বাগাতিপাড়া'),
(45, 'Baraigram', 'বড়াইগ্রাম'),
(45, 'Gurudaspur', 'গুরুদাসপুর'),
(45, 'Lalpur', 'লালপুর'),
(45, 'Natore Sadar', 'নাটোর সদর'),
(45, 'Naldanga', 'নলডাঙ্গা'),
(45, 'Singra', 'সিংড়া'),

-- Pabna District (id: 46)
(46, 'Atgharia', 'আটঘরিয়া'),
(46, 'Bera', 'বেড়া'),
(46, 'Bhangura', 'ভাঙ্গুড়া'),
(46, 'Chatmohar', 'চাটমোহর'),
(46, 'Faridpur', 'ফরিদপুর'),
(46, 'Ishwardi', 'ঈশ্বরদী'),
(46, 'Pabna Sadar', 'পাবনা সদর'),
(46, 'Santhia', 'সাঁথিয়া'),
(46, 'Sujanagar', 'সুজানগর'),

-- Rajshahi District (id: 47)
(47, 'Bagha', 'বাঘা'),
(47, 'Bagmara', 'বাগমারা'),
(47, 'Charghat', 'চারঘাট'),
(47, 'Durgapur', 'দুর্গাপুর'),
(47, 'Godagari', 'গোদাগাড়ী'),
(47, 'Mohanpur', 'মোহনপুর'),
(47, 'Paba', 'পবা'),
(47, 'Puthia', 'পুঠিয়া'),
(47, 'Tanore', 'তানোর'),

-- Sirajganj District (id: 48)
(48, 'Belkuchi', 'বেলকুচি'),
(48, 'Chauhali', 'চৌহালি'),
(48, 'Kamarkhanda', 'কামারখন্দ'),
(48, 'Kazipur', 'কাজীপুর'),
(48, 'Raiganj', 'রায়গঞ্জ'),
(48, 'Shahjadpur', 'শাহজাদপুর'),
(48, 'Sirajganj Sadar', 'সিরাজগঞ্জ সদর'),
(48, 'Tarash', 'তাড়াশ'),
(48, 'Ullahpara', 'উল্লাপাড়া'),

-- Dinajpur District (id: 49)
(49, 'Birampur', 'বিরামপুর'),
(49, 'Birganj', 'বীরগঞ্জ'),
(49, 'Biral', 'বিরল'),
(49, 'Bochaganj', 'বোচাগঞ্জ'),
(49, 'Chirirbandar', 'চিরিরবন্দর'),
(49, 'Dinajpur Sadar', 'দিনাজপুর সদর'),
(49, 'Fulbari', 'ফুলবাড়ী'),
(49, 'Ghoraghat', 'ঘোড়াঘাট'),
(49, 'Hakimpur', 'হাকিমপুর'),
(49, 'Kaharole', 'কাহারোল'),
(49, 'Khansama', 'খানসামা'),
(49, 'Nawabganj', 'নবাবগঞ্জ'),
(49, 'Parbatipur', 'পার্বতীপুর'),

-- Gaibandha District (id: 50)
(50, 'Fulchhari', 'ফুলছড়ি'),
(50, 'Gaibandha Sadar', 'গাইবান্ধা সদর'),
(50, 'Gobindaganj', 'গোবিন্দগঞ্জ'),
(50, 'Palashbari', 'পলাশবাড়ী'),
(50, 'Sadullapur', 'সাদুল্লাপুর'),
(50, 'Saghata', 'সাঘাটা'),
(50, 'Sundarganj', 'সুন্দরগঞ্জ'),

-- Kurigram District (id: 51)
(51, 'Bhurungamari', 'ভুরুঙ্গামারী'),
(51, 'Char Rajibpur', 'চর রাজিবপুর'),
(51, 'Chilmari', 'চিলমারী'),
(51, 'Kurigram Sadar', 'কুড়িগ্রাম সদর'),
(51, 'Nageshwari', 'নাগেশ্বরী'),
(51, 'Phulbari', 'ফুলবাড়ী'),
(51, 'Rajarhat', 'রাজারহাট'),
(51, 'Raumari', 'রৌমারী'),
(51, 'Ulipur', 'উলিপুর'),

-- Lalmonirhat District (id: 52)
(52, 'Aditmari', 'আদিতমারী'),
(52, 'Hatibandha', 'হাতীবান্ধা'),
(52, 'Kaliganj', 'কালীগঞ্জ'),
(52, 'Lalmonirhat Sadar', 'লালমনিরহাট সদর'),
(52, 'Patgram', 'পাটগ্রাম'),

-- Nilphamari District (id: 53)
(53, 'Dimla', 'ডিমলা'),
(53, 'Domar', 'ডোমার'),
(53, 'Jaldhaka', 'জলঢাকা'),
(53, 'Kishoreganj', 'কিশোরগঞ্জ'),
(53, 'Nilphamari Sadar', 'নীলফামারী সদর'),
(53, 'Saidpur', 'সৈয়দপুর'),

-- Panchagarh District (id: 54)
(54, 'Atwari', 'আটোয়ারী'),
(54, 'Boda', 'বোদা'),
(54, 'Debiganj', 'দেবীগঞ্জ'),
(54, 'Panchagarh Sadar', 'পঞ্চগড় সদর'),
(54, 'Tetulia', 'তেতুলিয়া'),

-- Rangpur District (id: 55)
(55, 'Badarganj', 'বদরগঞ্জ'),
(55, 'Gangachara', 'গঙ্গাচড়া'),
(55, 'Kaunia', 'কাউনিয়া'),
(55, 'Mithapukur', 'মিঠাপুকুর'),
(55, 'Pirgachha', 'পীরগাছা'),
(55, 'Pirganj', 'পীরগঞ্জ'),
(55, 'Rangpur Sadar', 'রংপুর সদর'),
(55, 'Taraganj', 'তারাগঞ্জ'),

-- Thakurgaon District (id: 56)
(56, 'Baliadangi', 'বালিয়াডাঙ্গী'),
(56, 'Haripur', 'হরিপুর'),
(56, 'Pirganj', 'পীরগঞ্জ'),
(56, 'Ranisankail', 'রাণীশংকৈল'),
(56, 'Thakurgaon Sadar', 'ঠাকুরগাঁও সদর'),

-- Habiganj District (id: 57)
(57, 'Ajmiriganj', 'আজমিরীগঞ্জ'),
(57, 'Bahubal', 'বাহুবল'),
(57, 'Baniachong', 'বানিয়াচং'),
(57, 'Chunarughat', 'চুনারুঘাট'),
(57, 'Habiganj Sadar', 'হবিগঞ্জ সদর'),
(57, 'Lakhai', 'লাখাই'),
(57, 'Madhabpur', 'মাধবপুর'),
(57, 'Nabiganj', 'নবীগঞ্জ'),

-- Moulvibazar District (id: 58)
(58, 'Barlekha', 'বড়লেখা'),
(58, 'Juri', 'জুড়ী'),
(58, 'Kamalganj', 'কমলগঞ্জ'),
(58, 'Kulaura', 'কুলাউড়া'),
(58, 'Moulvibazar Sadar', 'মৌলভীবাজার সদর'),
(58, 'Rajnagar', 'রাজনগর'),
(58, 'Sreemangal', 'শ্রীমঙ্গল'),

-- Sunamganj District (id: 59)
(59, 'Bishwamvarpur', 'বিশ্বম্ভরপুর'),
(59, 'Chhatak', 'ছাতক'),
(59, 'Derai', 'দিরাই'),
(59, 'Dharampasha', 'ধরমপাশা'),
(59, 'Dowarabazar', 'দোয়ারাবাজার'),
(59, 'Jagannathpur', 'জগন্নাথপুর'),
(59, 'Jamalganj', 'জামালগঞ্জ'),
(59, 'Sulla', 'সুল্লা'),
(59, 'Sunamganj Sadar', 'সুনামগঞ্জ সদর'),
(59, 'Tahirpur', 'তাহিরপুর'),
(59, 'South Sunamganj', 'দক্ষিণ সুনামগঞ্জ'),
(59, 'Madhyanagar', 'মধ্যনগর'),

-- Sylhet District (id: 60)
(60, 'Balaganj', 'বালাগঞ্জ'),
(60, 'Beanibazar', 'বিয়ানীবাজার'),
(60, 'Bishwanath', 'বিশ্বনাথ'),
(60, 'Companiganj', 'কোম্পানীগঞ্জ'),
(60, 'Dakshin Surma', 'দক্ষিণ সুরমা'),
(60, 'Fenchuganj', 'ফেঞ্চুগঞ্জ'),
(60, 'Golapganj', 'গোলাপগঞ্জ'),
(60, 'Gowainghat', 'গোয়াইনঘাট'),
(60, 'Jaintiapur', 'জৈন্তাপুর'),
(60, 'Kanaighat', 'কানাইঘাট'),
(60, 'Osmani Nagar', 'ওসমানী নগর'),
(60, 'Sylhet Sadar', 'সিলেট সদর'),
(60, 'Zakiganj', 'জকিগঞ্জ'),

-- Jamalpur District (id: 61)
(61, 'Bakshiganj', 'বকশীগঞ্জ'),
(61, 'Dewanganj', 'দেওয়ানগঞ্জ'),
(61, 'Islampur', 'ইসলামপুর'),
(61, 'Jamalpur Sadar', 'জামালপুর সদর'),
(61, 'Madarganj', 'মাদারগঞ্জ'),
(61, 'Melandaha', 'মেলান্দহ'),
(61, 'Sarishabari', 'সরিষাবাড়ী'),

-- Mymensingh District (id: 62)
(62, 'Bhaluka', 'ভালুকা'), 
(62, 'Dhobaura', 'ধোবাউড়া'), 
(62, 'Fulbaria', 'ফুলবাড়ীয়া'), 
(62, 'Gaffargaon', 'গফরগাঁও'), 
(62, 'Gauripur', 'গৌরীপুর'), 
(62, 'Haluaghat', 'হালুয়াঘাট'), 
(62, 'Ishwarganj', 'ঈশ্বরগঞ্জ'),
(62, 'Mymensingh Sadar', 'ময়মনসিংহ সদর'),
(62, 'Muktagacha', 'মুক্তাগাছা'),
(62, 'Nandail', 'নান্দাইল'),
(62, 'Phulpur', 'ফুলপুর'),
(62, 'Trishal', 'ত্রিশাল'),
(62, 'Tarakanda', 'তারাকান্দা'),

-- Jamalpur District (id: 63)
(63, 'Jamalpur Sadar', 'জামালপুর সদর'),
(63, 'Melandah', 'মেলান্দহ'),
(63, 'Islampur', 'ইসলামপুর'),
(63, 'Dewanganj', 'দেওয়ানগঞ্জ'),
(63, 'Sarishabari', 'সরিষাবাড়ী'),
(63, 'Madarganj', 'মাদারগঞ্জ'),
(63, 'Bokshiganj', 'বকশীগঞ্জ'),

-- Netrokona District (id: 64)
(64, 'Barhatta', 'বারহাট্টা'),
(64, 'Durgapur', 'দুর্গাপুর'),
(64, 'Kendua', 'কেন্দুয়া'),
(64, 'Atpara', 'আটপাড়া'),
(64, 'Madan', 'মদন'),
(64, 'Khaliajuri', 'খালিয়াজুরী'),
(64, 'Kalmakanda', 'কলমাকান্দা'),
(64, 'Mohonganj', 'মোহনগঞ্জ'),
(64, 'Purbadhala', 'পূর্বধলা'),
(64, 'Netrokona Sadar', 'নেত্রকোনা সদর');