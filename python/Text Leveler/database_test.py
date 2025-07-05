# 127.0.0.1
# root
# ck_agent

import mysql.connector

# Connect to the database
mydb = mysql.connector.connect(
    host="127.0.0.1",
    user="root",
    password="",
    database="ck_agent",
)

agent_id = "4"
parameter_id = "4"
input = "Grade 5"

user_id = "1"
message_id = "1"
sender = "human"
topic = "Sample Topic"

mycursor = mydb.cursor()

# Insert into parameter_inputs table
sql_param = """
INSERT INTO parameter_inputs (input, parameter_id, agent_id) VALUES (%s, %s, %s)
"""
val_param = (input, parameter_id, agent_id)
mycursor.execute(sql_param, val_param)

parameter_input_id = mycursor.lastrowid

# Insert into messages table
sql_msg = """
INSERT INTO messages (agent_id, user_id, message_id, parameter_inputs, sender, topic)
VALUES (%s, %s, %s, %s, %s, %s)
"""
val_msg = (agent_id, user_id, message_id, parameter_input_id, sender, topic)
mycursor.execute(sql_msg, val_msg)

# Commit the transaction
mydb.commit()

print(mycursor.rowcount, "record(s) inserted.")