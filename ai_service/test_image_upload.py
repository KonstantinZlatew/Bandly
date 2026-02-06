import requests

url = "http://127.0.0.1:8000/evaluate-form"

# Prepare form data
data = {
    'task_type': 'academic_task_1',
    'task_prompt': 'The chart below shows the percentage of households with internet access in three countries from 2000 to 2010.',
    'essay': 'The graph illustrates the proportion of households with internet connectivity in three different countries over a ten-year period from 2000 to 2010. Overall, all three countries experienced significant growth in internet penetration, with Country A showing the most dramatic increase.'
}

# Open and upload the image file
with open('D:\Koko\koko.jpg', 'rb') as image_file:
    files = {
        'image': image_file
    }
    
    response = requests.post(url, files=files, data=data)
    print(response.json())