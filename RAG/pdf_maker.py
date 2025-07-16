from fpdf import FPDF

pdf = FPDF()
pdf.add_page()
pdf.set_auto_page_break(auto=True, margin=15)

# ✅ Use a built-in font (no font file needed)
pdf.set_font("Helvetica", size=12)

# Page 1 content
chapter1 = """
Chapter 1: Introduction to Robotics

Robotics is the interdisciplinary field focused on the design, construction, operation, and use of robots. A robot is a machine—especially one programmable by a computer—capable of carrying out complex tasks automatically.

Robots can be autonomous or semi-autonomous and range from humanoids to industrial arms. Robotics integrates mechanical engineering, electrical engineering, computer science, and artificial intelligence.

Applications include manufacturing, surgery, disaster response, and more.
"""

# Page 2 content
chapter2 = """
Chapter 2: Sensors and Actuators

Sensors collect data from the physical environment, while actuators act upon it. Common sensors include cameras, LiDAR, infrared, and ultrasonic sensors.

Actuators include motors, servos, and hydraulic systems. Together, they form the core of robot perception and motion.

The integration of these components allows robots to sense and interact with their surroundings intelligently.
"""

chapter1 = chapter1.replace("—", "-").replace("“", "\"").replace("”", "\"")


# Add pages
pdf.add_page()
pdf.multi_cell(0, 10, chapter1)

pdf.add_page()
pdf.multi_cell(0, 10, chapter2)

pdf.output("sample_robotics.pdf", "F")
print("✅ sample_robotics.pdf created")
